<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportIdDataCommand extends Command
{
    protected $signature = 'import:id-data
                            {--file= : Path to ID Data CSV file}
                            {--dry-run : Run without actually inserting data}';

    protected $description = 'Import officers data from ID Data.csv into mc_officers table';

    protected string $table = 'mc_officers';

    private array $stats = [
        'processed' => 0,
        'created' => 0,
        'skipped_duplicate' => 0,
        'skipped_empty' => 0,
        'errors' => [],
    ];

    public function handle(): int
    {
        $filePath = $this->resolveFilePath();
        $dryRun = $this->option('dry-run');

        if (!$filePath || !file_exists($filePath)) {
            $this->error("CSV file not found: " . ($filePath ?: 'No file specified'));
            $this->warn("Usage: php artisan import:id-data --file=\"path/to/ID Data.csv\"");
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        $this->info("Starting import from: {$filePath}");

        // Check database connection
        if (!$dryRun) {
            try {
                DB::connection()->getPdo();
                $this->info("Database connection OK");
            } catch (\Exception $e) {
                $this->error("Database connection failed: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Could not read CSV header");
            fclose($handle);
            return Command::FAILURE;
        }

        $this->info("CSV Header: " . implode(', ', $header));

        // Count total rows for progress bar
        $totalRows = 0;
        $currentPos = ftell($handle);
        while (fgetcsv($handle) !== false) {
            $totalRows++;
        }
        fseek($handle, $currentPos);

        $this->info("Total rows to process: {$totalRows}");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalRows);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Created: %message%');
        $bar->setMessage('0');

        // Load existing military numbers to skip duplicates efficiently
        $existingMilitaryNumbers = [];
        $existingNationalIds = [];
        if (!$dryRun) {
            $existingMilitaryNumbers = DB::table($this->table)
                ->whereNull('deleted_at')
                ->pluck('military_number')
                ->map(fn($v) => trim($v))
                ->flip()
                ->toArray();

            $existingNationalIds = DB::table($this->table)
                ->whereNull('deleted_at')
                ->whereNotNull('national_id')
                ->pluck('national_id')
                ->map(fn($v) => trim($v))
                ->flip()
                ->toArray();

            $this->info("Existing officers in DB: " . count($existingMilitaryNumbers));
        }

        // Process rows in chunks for better DB performance
        $chunk = [];
        $chunkSize = 500;

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();
            $this->stats['processed']++;

            try {
                $parsed = $this->parseRow($row);

                if (!$parsed) {
                    $this->stats['skipped_empty']++;
                    continue;
                }

                $militaryNumber = $parsed['military_number'];

                // Skip if military number already exists
                if (isset($existingMilitaryNumbers[$militaryNumber])) {
                    $this->stats['skipped_duplicate']++;
                    continue;
                }

                // Generate national_id if missing or resolve collisions
                if (empty($parsed['national_id']) || isset($existingNationalIds[$parsed['national_id']])) {
                    $base = $this->generateDefaultNationalId($militaryNumber);
                    $nationalId = $base;
                    $counter = 1;
                    while (isset($existingNationalIds[$nationalId])) {
                        // Append counter to the base, keeping it 14 digits
                        $counterStr = (string) $counter;
                        $nationalId = substr($base, 0, 14 - strlen($counterStr)) . $counterStr;
                        $counter++;
                    }
                    $parsed['national_id'] = $nationalId;
                }

                // Track this entry to avoid duplicates within the same import
                $existingMilitaryNumbers[$militaryNumber] = true;
                $existingNationalIds[$parsed['national_id']] = true;

                $record = [
                    'national_id'      => $parsed['national_id'],
                    'full_name'        => $parsed['full_name'],
                    'rank'             => $parsed['rank'],
                    'weapon_type'      => $parsed['weapon_type'],
                    'service_status'   => $parsed['service_status'],
                    'is_staff_officer' => $parsed['is_staff_officer'],
                    'seniority_number' => $parsed['seniority_number'],
                    'military_number'  => $parsed['military_number'],
                    'membership_id'    => $parsed['membership_id'],
                    'age'              => $parsed['age'],
                    'notes'            => $parsed['notes'],
                    'photo'            => null, // Skip images as requested
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                if (!$dryRun) {
                    $chunk[] = $record;

                    if (count($chunk) >= $chunkSize) {
                        DB::table($this->table)->insert($chunk);
                        $this->stats['created'] += count($chunk);
                        $bar->setMessage((string)$this->stats['created']);
                        $chunk = [];
                    }
                } else {
                    $this->stats['created']++;
                    $bar->setMessage((string)$this->stats['created']);
                }
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row {$this->stats['processed']}: " . $e->getMessage();
            }
        }

        // Insert remaining chunk
        if (!$dryRun && !empty($chunk)) {
            try {
                DB::table($this->table)->insert($chunk);
                $this->stats['created'] += count($chunk);
                $bar->setMessage((string)$this->stats['created']);
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Final chunk insert error: " . $e->getMessage();
            }
        }

        $bar->finish();
        $this->newLine(2);
        fclose($handle);

        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Parse a single CSV row into an officer data array.
     *
     * CSV columns:
     *   0: ID       - old system row ID (not used)
     *   1: a        - military number (الرقم العسكري)
     *   2: b        - rank (الرتبة)
     *   3: c        - service status (الحالة)
     *   4: d        - full name (الاسم)
     *   5: e        - membership ID (رقم العضوية)
     *   6: f        - (empty)
     *   7: g        - (empty)
     *   8: h        - image path (skipped)
     *   9: i        - notes (ملاحظات)
     *  10: j        - additional notes (e.g. batch number)
     *  11: k        - (empty)
     *  12: l        - (empty)
     *  13: m        - (empty)
     *  14: ageee    - age
     *  15: bdate    - birth date
     *  16: z        - national ID (الرقم القومي) - 14 digits
     *  17: oldno    - old number
     */
    private function parseRow(array $row): ?array
    {
        if (count($row) < 6) {
            return null;
        }

        $militaryNumber = trim($row[1] ?? '');
        $rank           = trim($row[2] ?? '');
        $statusText     = trim($row[3] ?? '');
        $fullName       = trim($row[4] ?? '');
        $membershipId   = trim($row[5] ?? '');
        // index 8 = image path (skip)
        $notes          = trim($row[9] ?? '');
        $additionalNote = trim($row[10] ?? '');
        $age            = trim($row[14] ?? '');
        $nationalId     = trim($row[16] ?? '');
        $oldNo          = trim($row[17] ?? '');

        // Normalize military number - remove all whitespace
        $militaryNumber = preg_replace('/\s+/', '', $militaryNumber);

        // Skip rows missing mandatory data
        if (empty($militaryNumber) || empty($fullName)) {
            return null;
        }

        // Combine notes from different columns
        $allNotes = $this->buildNotes($notes, $additionalNote);

        // Map service status
        $serviceStatus = $this->mapServiceStatus($statusText);

        // Detect staff officer from rank (contains أ.ح or أح)
        $isStaffOfficer = $this->isStaffOfficer($rank);

        // Weapon type - default infantry
        $weaponType = $this->mapWeaponType($statusText);

        // Validate national ID (must be exactly 14 digits)
        $validNationalId = null;
        if (!empty($nationalId) && preg_match('/^\d{14}$/', $nationalId)) {
            $validNationalId = $nationalId;
        }

        // Parse age
        $parsedAge = null;
        if (!empty($age) && is_numeric($age)) {
            $parsedAge = (int) $age;
            if ($parsedAge < 0 || $parsedAge > 150) {
                $parsedAge = null;
            }
        }

        // Seniority number from oldno column
        $seniorityNumber = !empty($oldNo) ? $oldNo : null;

        return [
            'military_number'  => $militaryNumber,
            'full_name'        => $fullName,
            'rank'             => $rank,
            'service_status'   => $serviceStatus,
            'is_staff_officer' => $isStaffOfficer,
            'weapon_type'      => $weaponType,
            'membership_id'    => !empty($membershipId) ? $membershipId : null,
            'national_id'      => $validNationalId,
            'age'              => $parsedAge,
            'notes'            => $allNotes,
            'seniority_number' => $seniorityNumber,
        ];
    }

    private function buildNotes(?string $notes, ?string $additionalNote): ?string
    {
        $parts = [];

        if (!empty($notes)) {
            $parts[] = $notes;
        }
        if (!empty($additionalNote)) {
            $parts[] = $additionalNote;
        }

        return !empty($parts) ? implode(' | ', $parts) : null;
    }

    private function mapServiceStatus(string $statusText): ?string
    {
        $statusText = trim($statusText);

        if (empty($statusText)) {
            return null;
        }

        // "م" is abbreviation for معاش (retired)
        if ($statusText === 'م') {
            return 'retired';
        }

        if (str_contains($statusText, 'متوفى') || str_contains($statusText, 'متوفي')) {
            return 'deceased';
        }

        if (str_contains($statusText, 'شهيد')) {
            return 'martyr';
        }

        if (str_contains($statusText, 'متقاعد') || str_contains($statusText, 'معاش')) {
            return 'retired';
        }

        if (str_contains($statusText, 'بالخدمة')) {
            return 'recalled';
        }

        if (str_contains($statusText, 'منقول')) {
            return 'transferred';
        }

        return null;
    }

    private function mapWeaponType(string $statusText): string
    {
        $statusText = trim($statusText);

        if (str_contains($statusText, 'أسلحة أخرى') || str_contains($statusText, 'أخرى')) {
            return 'other';
        }

        return 'infantry';
    }

    private function isStaffOfficer(string $rank): bool
    {
        $rank = trim($rank);

        // Various formats for أركان حرب
        return str_contains($rank, 'أ.ح')
            || str_contains($rank, 'أح')
            || str_contains($rank, 'ا.ح')
            || str_contains($rank, 'اح');
    }

    private function generateDefaultNationalId(string $militaryNumber): string
    {
        // Pad military number with leading zeros to make it exactly 14 digits
        $digits = preg_replace('/\D/', '', $militaryNumber);

        return str_pad($digits, 14, '0', STR_PAD_LEFT);
    }

    private function resolveFilePath(): ?string
    {
        $filePath = $this->option('file');

        if ($filePath) {
            if (file_exists($filePath)) {
                return $filePath;
            }

            // Try relative to current directory
            $cwdFile = getcwd() . '/' . $filePath;
            if (file_exists($cwdFile)) {
                return $cwdFile;
            }

            // Try relative to base path
            $baseFile = base_path($filePath);
            if (file_exists($baseFile)) {
                return $baseFile;
            }

            return $filePath; // Return as-is; handle() will report "not found"
        }

        // Default path
        $defaultPath = base_path('ID Data.csv');
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return $defaultPath;
    }

    private function displayStats(): void
    {
        $this->info('=== Import Statistics ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows Processed', $this->stats['processed']],
                ['Officers Created', $this->stats['created']],
                ['Skipped (Duplicate)', $this->stats['skipped_duplicate']],
                ['Skipped (Empty/Invalid)', $this->stats['skipped_empty']],
                ['Errors', count($this->stats['errors'])],
            ]
        );

        if (!empty($this->stats['errors'])) {
            $this->newLine();
            $this->warn('Errors encountered:');

            $uniqueErrors = array_unique(array_slice($this->stats['errors'], 0, 50));
            foreach (array_slice($uniqueErrors, 0, 15) as $error) {
                $this->error("  " . $error);
            }

            if (count($this->stats['errors']) > 15) {
                $this->warn('  ... and ' . (count($this->stats['errors']) - 15) . ' more errors');
            }
        }
    }
}
