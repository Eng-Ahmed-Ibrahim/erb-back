<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBeneficiaryDataCommand extends Command
{
    protected $signature = 'import:beneficiary-data
                            {--file= : Path to ID Data 2.csv file}
                            {--dry-run : Run without actually inserting data}';

    protected $description = 'Import beneficiaries data from ID Data 2.csv into mc_beneficiaries table';

    protected string $officersTable = 'mc_officers';
    protected string $beneficiariesTable = 'mc_beneficiaries';

    private array $stats = [
        'processed' => 0,
        'created' => 0,
        'skipped_no_officer' => 0,
        'skipped_no_relationship' => 0,
        'skipped_too_few_columns' => 0,
        'skipped_no_name' => 0,
        'skipped_no_military_number' => 0,
        'skipped_invalid_military_number' => 0,
        'skipped_no_name_and_no_mil' => 0,
        'errors' => [],
    ];

    /**
     * Mapping of Arabic relationship terms to English enum values.
     */
    private array $relationshipMap = [
        'حرمه'    => 'spouse',
        'حرمة'    => 'spouse',
        'حرمته'   => 'spouse',
        'زوجته'   => 'spouse',
        'زوجه'    => 'spouse',
        'كريمته'  => 'child',
        'كريمة'   => 'child',
        'ابنته'   => 'child',
        'نجله'    => 'child',
        'نجلة'    => 'child',
        'ابنه'    => 'child',
        'الوالده' => 'parent',
        'الوالدة' => 'parent',
        'الوالد'  => 'parent',
        'والدته'  => 'parent',
        'والده'   => 'parent',
        'والدة'   => 'parent',
        'والد'    => 'parent',
        'فوق السن' => 'over_age',
    ];

    public function handle(): int
    {
        $filePath = $this->resolveFilePath();
        $dryRun = $this->option('dry-run');

        if (!$filePath || !file_exists($filePath)) {
            $this->error("CSV file not found: " . ($filePath ?: 'No file specified'));
            $this->warn("Usage: php artisan import:beneficiary-data --file=\"path/to/ID Data 2.csv\"");
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        $this->info("Starting beneficiary import from: {$filePath}");

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

        // Pre-load officer military_number → id mapping
        $officerMap = [];
        if (!$dryRun) {
            $officerMap = DB::table($this->officersTable)
                ->whereNull('deleted_at')
                ->pluck('id', 'military_number')
                ->mapWithKeys(fn($id, $milNum) => [trim($milNum) => $id])
                ->toArray();

            $this->info("Officers loaded from DB: " . count($officerMap));
        }

        // Process rows in chunks for better DB performance
        $chunk = [];
        $chunkSize = 500;

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();
            $this->stats['processed']++;

            try {
                $parsed = $this->parseRow($row);

                if (isset($parsed['_skip'])) {
                    $key = 'skipped_' . $parsed['_skip'];
                    if (isset($this->stats[$key])) {
                        $this->stats[$key]++;
                    }
                    continue;
                }

                if (!$parsed['relationship_type']) {
                    $this->stats['skipped_no_relationship']++;
                    continue;
                }

                $officerMilitaryNumber = $parsed['officer_military_number'];

                // Look up officer_id
                $officerId = $officerMap[$officerMilitaryNumber] ?? null;

                if (!$officerId && !$dryRun) {
                    $this->stats['skipped_no_officer']++;
                    continue;
                }

                $record = [
                    'officer_id'        => $officerId ?? 0,
                    'full_name'         => $parsed['full_name'],
                    'relationship_type' => $parsed['relationship_type'],
                    'birth_date'        => $parsed['birth_date'],
                    'national_id'       => $parsed['national_id'],
                    'family_index'      => $parsed['family_index'],
                    'notes'             => $parsed['notes'],
                    'photo'             => null, // Skip images
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];

                if (!$dryRun) {
                    $chunk[] = $record;

                    if (count($chunk) >= $chunkSize) {
                        $this->insertChunk($chunk);
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
            $this->insertChunk($chunk);
            $bar->setMessage((string)$this->stats['created']);
        }

        $bar->finish();
        $this->newLine(2);
        fclose($handle);

        $this->displayStats();

        return Command::SUCCESS;
    }

    /**
     * Parse a single CSV row into a beneficiary data array.
     *
     * CSV columns (ID Data 2.csv):
     *   0: ID    - old system row ID (not used)
     *   1: a     - full name (الاسم)
     *   2: b     - relationship type (صلة القرابة) in Arabic
     *   3: c     - unknown number
     *   4: d     - age (السن)
     *   5: e     - officer's military number (الرقم العسكري للضابط) - used to link
     *   6: f     - image path (skipped)
     *   7: g     - family index (ترتيب الأسرة)
     *   8: h     - empty
     *   9: i     - notes (ملاحظات)
     *  10: j     - birth date (تاريخ الميلاد)
     *  11: k     - birth year component
     *  12: l     - birth month component
     *  13: m     - birth day component
     *  14: z     - national ID (الرقم القومي) - 14 digits
     *  15: oldno - old number
     */
    /**
     * Return value: array with data on success, or ['_skip' => 'reason'] on skip.
     */
    private function parseRow(array $row): array
    {
        if (count($row) < 6) {
            return ['_skip' => 'too_few_columns'];
        }

        $fullName               = trim($row[1] ?? '');
        $relationshipText       = trim($row[2] ?? '');
        $age                    = trim($row[4] ?? '');
        $officerMilitaryNumber  = trim($row[5] ?? '');
        // index 6 = image path (skip)
        $familyIndex            = trim($row[7] ?? '');
        $notes                  = trim($row[9] ?? '');
        $birthDateStr           = trim($row[10] ?? '');
        $nationalId             = trim($row[14] ?? '');

        // Normalize officer military number - remove all whitespace
        $officerMilitaryNumber = preg_replace('/\s+/', '', $officerMilitaryNumber);

        // Granular skip reasons
        if (empty($fullName) && empty($officerMilitaryNumber)) {
            return ['_skip' => 'no_name_and_no_mil'];
        }
        if (empty($fullName)) {
            return ['_skip' => 'no_name'];
        }
        if (empty($officerMilitaryNumber)) {
            return ['_skip' => 'no_military_number'];
        }

        // Skip if the officer military number is not numeric (bad data like rank text)
        if (!preg_match('/^\d+$/', $officerMilitaryNumber)) {
            return ['_skip' => 'invalid_military_number'];
        }

        // Map relationship type
        $relationshipType = $this->mapRelationshipType($relationshipText);

        // Parse birth date
        $birthDate = $this->parseBirthDate($birthDateStr, $row);

        // If no birth date but age is available, estimate birth date from age
        if (!$birthDate && !empty($age) && is_numeric($age)) {
            $ageInt = (int) $age;
            if ($ageInt > 0 && $ageInt < 150) {
                try {
                    $birthDate = Carbon::now()->subYears($ageInt)->startOfYear()->format('Y-m-d');
                } catch (\Exception $e) {
                    $birthDate = null;
                }
            }
        }

        // Validate national ID (must be exactly 14 digits)
        $validNationalId = null;
        if (!empty($nationalId) && preg_match('/^\d{14}$/', $nationalId)) {
            $validNationalId = $nationalId;
        }

        // Parse family index
        $parsedFamilyIndex = null;
        if (!empty($familyIndex) && is_numeric($familyIndex)) {
            $fi = (int) $familyIndex;
            if ($fi >= 1) {
                $parsedFamilyIndex = $fi;
            }
        }

        return [
            'officer_military_number' => $officerMilitaryNumber,
            'full_name'               => $fullName,
            'relationship_type'       => $relationshipType,
            'birth_date'              => $birthDate,
            'national_id'             => $validNationalId,
            'family_index'            => $parsedFamilyIndex,
            'notes'                   => !empty($notes) ? $notes : null,
        ];
    }

    /**
     * Map Arabic relationship text to English enum values.
     */
    private function mapRelationshipType(string $text): ?string
    {
        $text = trim($text);

        if (empty($text)) {
            return null;
        }

        // Direct match
        if (isset($this->relationshipMap[$text])) {
            return $this->relationshipMap[$text];
        }

        // Fuzzy matching - check if the text contains known keywords
        foreach ($this->relationshipMap as $arabic => $english) {
            if (str_contains($text, $arabic)) {
                return $english;
            }
        }

        // Additional fuzzy patterns
        if (str_contains($text, 'حرم') || str_contains($text, 'زوج')) {
            return 'spouse';
        }
        if (str_contains($text, 'كريم') || str_contains($text, 'ابن') || str_contains($text, 'بنت')) {
            return 'child';
        }
        if (str_contains($text, 'نجل')) {
            return 'child';
        }
        if (str_contains($text, 'والد') || str_contains($text, 'أم') || str_contains($text, 'أب')) {
            return 'parent';
        }
        if (str_contains($text, 'حفيد')) {
            return 'grandchild';
        }
        if (str_contains($text, 'فوق السن')) {
            return 'over_age';
        }

        return null;
    }

    /**
     * Parse various date formats from the CSV.
     */
    private function parseBirthDate(string $dateStr, array $row): ?string
    {
        if (empty($dateStr)) {
            // Try to build from component columns (k, l, m → indices 11, 12, 13)
            $year  = trim($row[11] ?? '');
            $month = trim($row[12] ?? '');
            $day   = trim($row[13] ?? '');

            if (!empty($year) && !empty($month) && !empty($day)) {
                // If year is 2-digit, prefix with century
                if (strlen($year) === 2) {
                    $year = ((int)$year > 30 ? '19' : '20') . $year;
                }
                try {
                    return Carbon::createFromDate((int)$year, (int)$month, (int)$day)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }

            return null;
        }

        // Try common date formats
        $formats = [
            'd-m-Y',    // 18-05-1993
            'd/m/Y',    // 01/08/1985
            'Y-m-d',    // 1993-05-18
            'd-n-Y',    // 8-1-1989 (single digit month)
            'n-d-Y',    // edge case
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateStr);
                if ($date && $date->year > 1900 && $date->year <= Carbon::now()->year) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: let Carbon try to parse it naturally
        try {
            $date = Carbon::parse($dateStr);
            if ($date->year > 1900 && $date->year <= Carbon::now()->year) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Could not parse
        }

        return null;
    }

    /**
     * Insert a chunk of records. If the bulk insert fails, fall back to
     * inserting one-by-one so only truly broken rows are skipped.
     */
    private function insertChunk(array $chunk): void
    {
        try {
            DB::table($this->beneficiariesTable)->insert($chunk);
            $this->stats['created'] += count($chunk);
        } catch (\Exception $e) {
            // Bulk insert failed — fall back to individual inserts
            foreach ($chunk as $idx => $record) {
                try {
                    DB::table($this->beneficiariesTable)->insert($record);
                    $this->stats['created']++;
                } catch (\Exception $rowException) {
                    $name = $record['full_name'] ?? 'unknown';
                    $this->stats['errors'][] = "Row [{$name}]: " . $rowException->getMessage();
                }
            }
        }
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
        $defaultPath = base_path('ID Data 2.csv');
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        return $defaultPath;
    }

    private function displayStats(): void
    {
        $this->info('=== Beneficiary Import Statistics ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows Processed', $this->stats['processed']],
                ['Beneficiaries Created', $this->stats['created']],
                ['Skipped (No Officer Found)', $this->stats['skipped_no_officer']],
                ['Skipped (No Relationship)', $this->stats['skipped_no_relationship']],
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
