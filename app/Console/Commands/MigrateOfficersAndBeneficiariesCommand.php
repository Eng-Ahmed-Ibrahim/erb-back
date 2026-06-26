<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\MembershipCards\Application\Commands\CreateOfficerCommand;
use Modules\MembershipCards\Application\Commands\CreateBeneficiaryCommand;
use Modules\MembershipCards\Application\Handlers\CreateOfficerHandler;
use Modules\MembershipCards\Application\Handlers\CreateBeneficiaryHandler;
use Modules\MembershipCards\Application\DTOs\CreateOfficerDTO;
use Modules\MembershipCards\Application\DTOs\CreateBeneficiaryDTO;

class MigrateOfficersAndBeneficiariesCommand extends Command
{
    protected $signature = 'migrate:officers-beneficiaries 
                            {--officers-file= : Path to officers CSV file}
                            {--beneficiaries-file= : Path to beneficiaries CSV file}
                            {--dry-run : Run without actually inserting data}';

    protected $description = 'Migrate officers and beneficiaries from CSV files to the new system';

    private array $officerMap = []; // Maps old membership number to new officer ID
    private array $missingOfficers = []; // Track missing officer membership numbers
    private array $stats = [
        'officers_processed' => 0,
        'officers_created' => 0,
        'officers_skipped' => 0,
        'beneficiaries_processed' => 0,
        'beneficiaries_created' => 0,
        'beneficiaries_skipped' => 0,
        'errors' => []
    ];

    public function __construct(
        private CreateOfficerHandler $createOfficerHandler,
        private CreateBeneficiaryHandler $createBeneficiaryHandler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Resolve file paths - handle both absolute and relative paths
        $officersFile = $this->option('officers-file');
        if ($officersFile) {
            // If relative path, try to resolve it
            if (!file_exists($officersFile) && !str_starts_with($officersFile, '/')) {
                // Try relative to current directory
                $cwdFile = getcwd() . '/' . $officersFile;
                if (file_exists($cwdFile)) {
                    $officersFile = $cwdFile;
                } else {
                    // Try relative to base path
                    $baseFile = base_path($officersFile);
                    if (file_exists($baseFile)) {
                        $officersFile = $baseFile;
                    } else {
                        // Try parent directory
                        $parentFile = base_path('../' . $officersFile);
                        if (file_exists($parentFile)) {
                            $officersFile = $parentFile;
                        }
                    }
                }
            }
        } else {
            $officersFile = base_path('../Mahmoud.csv');
        }

        $beneficiariesFile = $this->option('beneficiaries-file');
        if ($beneficiariesFile) {
            // If relative path, try to resolve it
            if (!file_exists($beneficiariesFile) && !str_starts_with($beneficiariesFile, '/')) {
                // Try relative to current directory
                $cwdFile = getcwd() . '/' . $beneficiariesFile;
                if (file_exists($cwdFile)) {
                    $beneficiariesFile = $cwdFile;
                } else {
                    // Try relative to base path
                    $baseFile = base_path($beneficiariesFile);
                    if (file_exists($baseFile)) {
                        $beneficiariesFile = $baseFile;
                    } else {
                        // Try parent directory
                        $parentFile = base_path('../' . $beneficiariesFile);
                        if (file_exists($parentFile)) {
                            $beneficiariesFile = $parentFile;
                        }
                    }
                }
            }
        } else {
            $beneficiariesFile = base_path('../beneficials - Mahmoud.csv');
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        $this->info('Starting migration...');
        $this->info("Officers file: {$officersFile}");
        $this->info("Beneficiaries file: {$beneficiariesFile}");

        // Check database connection before starting
        if (!$dryRun) {
            $this->info("Checking database connection...");
            try {
                DB::connection()->getPdo();
                $this->info("✓ Database connection successful");
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("✗ Database connection failed!");
                $this->error("Error: " . $e->getMessage());
                $this->newLine();
                $this->warn("Please ensure:");
                $this->warn("  1. MySQL/MariaDB server is running");
                $this->warn("  2. Database credentials in .env are correct");
                $this->warn("  3. Database exists and is accessible");
                $this->newLine();
                $this->warn("You can test the connection with: php artisan db:show");
                $this->newLine();
                $this->warn("Note: You can use --dry-run to test the migration without database access");
                return Command::FAILURE;
            }
        } else {
            $this->info("✓ Dry run mode - skipping database connection check");
        }

        // Step 1: Migrate officers
        $this->info("\n=== Migrating Officers ===");
        $this->migrateOfficers($officersFile, $dryRun);

        // Step 2: Migrate beneficiaries
        $this->info("\n=== Migrating Beneficiaries ===");
        $this->migrateBeneficiaries($beneficiariesFile, $dryRun);

        // Step 3: Display statistics
        $this->displayStats();
        
        // Step 4: Display missing officers report if any
        if (!empty($this->missingOfficers)) {
            $this->displayMissingOfficersReport();
        }

        return Command::SUCCESS;
    }

    private function migrateOfficers(string $filePath, bool $dryRun): void
    {
        if (!file_exists($filePath)) {
            $this->error("Officers file not found: {$filePath}");
            $this->warn("Current working directory: " . getcwd());
            $this->warn("Base path: " . base_path());
            $this->warn("Please provide the full path or ensure the file exists in the expected location.");
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Could not read header from file");
            fclose($handle);
            return;
        }

        $this->info("Header: " . implode(', ', $header));

        // Count total lines for progress bar
        $totalLines = 0;
        $currentPos = ftell($handle);
        while (fgetcsv($handle) !== false) {
            $totalLines++;
        }
        fseek($handle, $currentPos);

        $bar = $this->output->createProgressBar($totalLines);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Processing officers...');

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();
            $this->stats['officers_processed']++;

            try {
                $officerData = $this->parseOfficerRow($row);
                if (!$officerData) {
                    $this->stats['officers_skipped']++;
                    continue;
                }

                if (!$dryRun) {
                    $officer = $this->createOfficer($officerData);
                    if ($officer) {
                        // Mapping is done inside createOfficer() method
                        $this->stats['officers_created']++;
                    } else {
                        $this->stats['officers_skipped']++;
                    }
                } else {
                    // In dry run, just simulate
                    $normalizedMembershipNumber = preg_replace('/\s+/', '', $officerData['old_membership_number']);
                    $this->officerMap[$normalizedMembershipNumber] = $this->stats['officers_processed'];
                    $this->stats['officers_created']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Officer row {$this->stats['officers_processed']}: " . $e->getMessage();
                $this->stats['officers_skipped']++;
            }
        }

        $bar->finish();
        $this->newLine();
        fclose($handle);
    }

    private function parseOfficerRow(array $row): ?array
    {
        // CSV columns: 
        // - ID: Old system ID
        // - a: Membership number (military number) - this is the key identifier used in DB
        // - b: Rank (Arabic)
        // - c: Service status/type (Arabic)
        // - d: Full name (Arabic)
        // - e: Seniority number or other identifier
        // - f-m: Empty columns
        if (count($row) < 5) {
            return null;
        }

        $id = trim($row[0] ?? '');
        // Column 'a' is the membership number (military number) - this is the key identifier
        $membershipNumber = trim($row[1] ?? '');
        $rank = trim($row[2] ?? '');
        $serviceStatusText = trim($row[3] ?? '');
        $fullName = trim($row[4] ?? '');
        // Column 'e' is the seniority number or another identifier
        $seniorityNumber = trim($row[5] ?? '');

        // Skip empty rows
        if (empty($membershipNumber) || empty($fullName)) {
            return null;
        }

        // Normalize membership number: remove all spaces and ensure it's clean
        $membershipNumber = preg_replace('/\s+/', '', $membershipNumber);

        // Map service status from Arabic to English
        $serviceStatus = $this->mapServiceStatus($serviceStatusText);
        
        // Map weapon type (default to infantry)
        $weaponType = $this->mapWeaponType($serviceStatusText);
        
        // Check if staff officer (اركان حرب)
        $isStaffOfficer = $this->isStaffOfficer($rank);

        // Generate a default national ID if missing (14 digits)
        // Using a pattern: 00000000000000 + last 4 digits of membership number
        $nationalId = $this->generateDefaultNationalId($membershipNumber);

        return [
            'old_id' => $id,
            'old_membership_number' => $membershipNumber, // Store normalized version for mapping
            'national_id' => $nationalId,
            'full_name' => $fullName,
            'rank' => $rank,
            'weapon_type' => $weaponType,
            'service_status' => $serviceStatus,
            'is_staff_officer' => $isStaffOfficer,
            'seniority_number' => !empty($seniorityNumber) ? $seniorityNumber : null,
            'membership_number' => $membershipNumber, // This is the military number (column 'a')
            'age' => null, // Not available in CSV
            'notes' => null,
            'photo' => null,
        ];
    }

    private function createOfficer(array $data): ?\Modules\MembershipCards\Domain\Entities\Officer
    {
        try {
            // Normalize membership number for consistent matching
            // The membership number (column 'a') is the military number
            $normalizedMembershipNumber = preg_replace('/\s+/', '', $data['membership_number']);
            
            // Check if officer already exists by membership number
            $existing = DB::table('mc_officers')
                ->where('membership_number', $data['membership_number'])
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                // Map normalized membership number to existing officer ID
                $this->officerMap[$normalizedMembershipNumber] = $existing->id;
                return null; // Already exists
            }

            // Check if national ID already exists
            $existingNationalId = DB::table('mc_officers')
                ->where('national_id', $data['national_id'])
                ->whereNull('deleted_at')
                ->first();

            if ($existingNationalId) {
                // Generate a new unique national ID based on membership number
                $data['national_id'] = $this->generateUniqueNationalId($data['membership_number']);
            }

            $dto = CreateOfficerDTO::fromArray($data);
            $command = new CreateOfficerCommand($dto);
            $officer = $this->createOfficerHandler->handle($command);
            
            // Store the normalized membership number (military number) in the map for beneficiary linking
            // Beneficiaries reference officers by this membership number
            $this->officerMap[$normalizedMembershipNumber] = $officer->getId();
            
            return $officer;
        } catch (\PDOException $e) {
            // Database connection errors
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'Connection refused') || str_contains($errorMsg, 'Connection refused')) {
                $this->stats['errors'][] = "Database connection error for officer {$data['full_name']}: Database server is not accessible. Please check your database connection.";
            } else {
                $this->stats['errors'][] = "Database error creating officer {$data['full_name']}: " . $errorMsg;
            }
            return null;
        } catch (\Exception $e) {
            $this->stats['errors'][] = "Error creating officer {$data['full_name']}: " . $e->getMessage();
            return null;
        }
    }

    private function migrateBeneficiaries(string $filePath, bool $dryRun): void
    {
        if (!file_exists($filePath)) {
            $this->error("Beneficiaries file not found: {$filePath}");
            $this->warn("Current working directory: " . getcwd());
            $this->warn("Base path: " . base_path());
            $this->warn("Please provide the full path or ensure the file exists in the expected location.");
            return;
        }
        
        // Check if officers were loaded
        if (empty($this->officerMap)) {
            $this->warn("No officers were loaded. Beneficiaries cannot be linked without officers.");
            $this->warn("Please ensure officers are migrated first.");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return;
        }

        // Skip header row
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Could not read header from file");
            fclose($handle);
            return;
        }

        // Count total lines for progress bar
        $totalLines = 0;
        $currentPos = ftell($handle);
        while (fgetcsv($handle) !== false) {
            $totalLines++;
        }
        fseek($handle, $currentPos);

        $bar = $this->output->createProgressBar($totalLines);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Processing beneficiaries...');

        while (($row = fgetcsv($handle)) !== false) {
            $bar->advance();
            $this->stats['beneficiaries_processed']++;

            try {
                $beneficiaryData = $this->parseBeneficiaryRow($row);
                if (!$beneficiaryData) {
                    $this->stats['beneficiaries_skipped']++;
                    continue;
                }

                // Find officer ID by membership number (military number)
                // The beneficiary references the officer by the membership number from column 'e'
                // which should match the membership number (column 'a') in the officers CSV
                $officerMembershipNumber = $beneficiaryData['officer_membership_number'];
                
                // Validate membership number is numeric (should have been validated in parseBeneficiaryRow, but double-check)
                if (!preg_match('/^\d+$/', $officerMembershipNumber)) {
                    $this->stats['beneficiaries_skipped']++;
                    continue; // Skip invalid membership numbers silently (already filtered in parseBeneficiaryRow)
                }
                
                // Try to find the officer - membership number should already be normalized
                if (!isset($this->officerMap[$officerMembershipNumber])) {
                    // Try with leading zeros or different formats
                    $found = false;
                    foreach ($this->officerMap as $mapKey => $officerId) {
                        // Remove leading zeros and compare
                        $normalizedKey = ltrim($mapKey, '0');
                        $normalizedSearch = ltrim($officerMembershipNumber, '0');
                        if ($normalizedKey === $normalizedSearch || $mapKey === $officerMembershipNumber) {
                            $officerMembershipNumber = $mapKey;
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $this->stats['beneficiaries_skipped']++;
                        // Track missing officers (count occurrences)
                        if (!isset($this->missingOfficers[$officerMembershipNumber])) {
                            $this->missingOfficers[$officerMembershipNumber] = 0;
                        }
                        $this->missingOfficers[$officerMembershipNumber]++;
                        
                        // Only log error if we have officers loaded (to avoid spam)
                        if (count($this->officerMap) > 0) {
                            $this->stats['errors'][] = "Officer not found for membership number: {$officerMembershipNumber}";
                        }
                        continue;
                    }
                }

                $beneficiaryData['officer_id'] = $this->officerMap[$officerMembershipNumber];

                if (!$dryRun) {
                    $beneficiary = $this->createBeneficiary($beneficiaryData);
                    if ($beneficiary) {
                        $this->stats['beneficiaries_created']++;
                    } else {
                        $this->stats['beneficiaries_skipped']++;
                    }
                } else {
                    $this->stats['beneficiaries_created']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors'][] = "Beneficiary row {$this->stats['beneficiaries_processed']}: " . $e->getMessage();
                $this->stats['beneficiaries_skipped']++;
            }
        }

        $bar->finish();
        $this->newLine();
        fclose($handle);
    }

    private function parseBeneficiaryRow(array $row): ?array
    {
        // CSV columns:
        // - ID: Old system ID
        // - a: Full name (Arabic)
        // - b: Relationship type (Arabic: حرمه, نجل, كريمته, الوالده, الوالد)
        // - c: Family index (number)
        // - d: Birth year
        // - e: Officer membership number (military number) - links to officer's column 'a'
        // - f: Photo path
        // - g: Birth month
        // - h: Birth day
        // - i-m: Other fields
        if (count($row) < 6) {
            return null;
        }

        $id = trim($row[0] ?? '');
        $name = trim($row[1] ?? '');
        $relationshipTypeArabic = trim($row[2] ?? '');
        $familyIndex = trim($row[3] ?? '');
        $birthYear = trim($row[4] ?? '');
        // Column 'e' contains the officer's membership number (military number) to link to officer
        $officerMembershipNumber = trim($row[5] ?? '');
        $photoPath = trim($row[6] ?? '');
        $birthMonth = trim($row[7] ?? '');
        $birthDay = trim($row[8] ?? '');

        // Skip empty rows
        if (empty($name) || empty($relationshipTypeArabic) || empty($officerMembershipNumber)) {
            return null;
        }

        // Normalize officer membership number: remove all spaces to match officer records
        $officerMembershipNumber = preg_replace('/\s+/', '', $officerMembershipNumber);
        
        // Validate membership number - must be numeric (military numbers are numeric)
        // Skip rows with invalid membership numbers (like Arabic text "لواءأح")
        if (!preg_match('/^\d+$/', $officerMembershipNumber)) {
            // Invalid membership number - skip this beneficiary
            return null;
        }

        // Map relationship type from Arabic to English
        $relationshipType = $this->mapRelationshipType($relationshipTypeArabic);
        if (!$relationshipType) {
            return null; // Unknown relationship type
        }

        // Parse birth date
        $birthDate = $this->parseBirthDate($birthYear, $birthMonth, $birthDay);

        // Parse family index
        $familyIndexInt = !empty($familyIndex) && is_numeric($familyIndex) ? (int)$familyIndex : null;

        return [
            'old_id' => $id,
            'officer_membership_number' => $officerMembershipNumber,
            'full_name' => $name,
            'relationship_type' => $relationshipType,
            'family_index' => $familyIndexInt,
            'birth_date' => $birthDate,
            'national_id' => null, // Not available in CSV
            'notes' => null,
            'photo' => !empty($photoPath) ? $photoPath : null,
        ];
    }

    private function createBeneficiary(array $data): ?\Modules\MembershipCards\Domain\Entities\Beneficiary
    {
        try {
            $dto = CreateBeneficiaryDTO::fromArray($data);
            $command = new CreateBeneficiaryCommand($dto);
            return $this->createBeneficiaryHandler->handle($command);
        } catch (\Exception $e) {
            $this->stats['errors'][] = "Error creating beneficiary {$data['full_name']}: " . $e->getMessage();
            return null;
        }
    }

    private function mapServiceStatus(string $statusText): ?string
    {
        $statusText = trim($statusText);
        
        if (str_contains($statusText, 'متقاعد')) {
            return 'retired';
        }
        if (str_contains($statusText, 'بالخدمة')) {
            return 'recalled'; // Active service
        }
        if (str_contains($statusText, 'متوفى')) {
            return 'deceased';
        }
        if (str_contains($statusText, 'معاش')) {
            return 'retired';
        }
        
        return null; // Unknown status
    }

    private function mapWeaponType(string $statusText): string
    {
        $statusText = trim($statusText);
        
        if (str_contains($statusText, 'أسلحة أخرى') || str_contains($statusText, 'أخرى')) {
            return 'other';
        }
        
        return 'infantry'; // Default
    }

    private function isStaffOfficer(string $rank): bool
    {
        $rank = trim($rank);
        // Check if rank contains "أ.ح" (اركان حرب)
        return str_contains($rank, 'أ.ح');
    }

    private function mapRelationshipType(string $arabicType): ?string
    {
        $arabicType = trim($arabicType);
        
        // Spouse variations
        if (in_array($arabicType, ['حرمه', 'الزوجه', 'زوجة', 'حرمه(مطلقة)'])) {
            return 'spouse';
        }
        
        // Child variations (son)
        if (in_array($arabicType, ['نجل', 'نجله', 'نجلى'])) {
            return 'child';
        }
        
        // Child variations (daughter)
        if ($arabicType === 'كريمته') {
            return 'child';
        }
        
        // Parent variations
        if ($arabicType === 'الوالده') {
            return 'parent';
        }
        if ($arabicType === 'الوالد') {
            return 'parent';
        }
        
        // Unknown relationship type
        return null;
    }

    private function parseBirthDate(?string $year, ?string $month, ?string $day): ?string
    {
        $year = trim($year ?? '');
        $month = trim($month ?? '');
        $day = trim($day ?? '');

        // Try to parse from year/month/day
        if (!empty($year) && is_numeric($year) && !empty($month) && is_numeric($month) && !empty($day) && is_numeric($day)) {
            $yearInt = (int)$year;
            $monthInt = (int)$month;
            $dayInt = (int)$day;
            
            // Handle 2-digit years
            if ($yearInt < 100) {
                $yearInt += 1900; // Assume 1900s
            }
            
            if ($yearInt >= 1900 && $yearInt <= date('Y') && $monthInt >= 1 && $monthInt <= 12 && $dayInt >= 1 && $dayInt <= 31) {
                try {
                    return sprintf('%04d-%02d-%02d', $yearInt, $monthInt, $dayInt);
                } catch (\Exception $e) {
                    // Invalid date
                }
            }
        }

        // Try to parse from year only
        if (!empty($year) && is_numeric($year)) {
            $yearInt = (int)$year;
            if ($yearInt < 100) {
                $yearInt += 1900;
            }
            if ($yearInt >= 1900 && $yearInt <= date('Y')) {
                // Use January 1st as default
                return sprintf('%04d-01-01', $yearInt);
            }
        }

        return null;
    }

    private function generateDefaultNationalId(string $membershipNumber): string
    {
        // Generate a 14-digit national ID
        // Pattern: Use the membership number (military number) padded to 14 digits
        $membershipNumber = preg_replace('/\D/', '', $membershipNumber); // Remove non-digits
        
        // If membership number is longer than 14, take last 14 digits
        // If shorter, pad with zeros at the beginning
        if (strlen($membershipNumber) >= 14) {
            $nationalId = substr($membershipNumber, -14);
        } else {
            $nationalId = str_pad($membershipNumber, 14, '0', STR_PAD_LEFT);
        }
        
        return $nationalId;
    }

    private function generateUniqueNationalId(string $membershipNumber): string
    {
        // Try to generate a unique national ID
        $base = $this->generateDefaultNationalId($membershipNumber);
        
        // Check if it exists, if so, modify it
        $counter = 0;
        $nationalId = $base;
        while (DB::table('mc_officers')->where('national_id', $nationalId)->whereNull('deleted_at')->exists()) {
            $counter++;
            $suffix = str_pad((string)$counter, 4, '0', STR_PAD_LEFT);
            $nationalId = substr('00000000' . $suffix, 0, 14);
            
            if ($counter > 9999) {
                // Fallback: use timestamp
                $nationalId = substr('000000' . time(), 0, 14);
                break;
            }
        }
        
        return $nationalId;
    }

    private function displayStats(): void
    {
        $this->newLine();
        $this->info('=== Migration Statistics ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Officers Processed', $this->stats['officers_processed']],
                ['Officers Created', $this->stats['officers_created']],
                ['Officers Skipped', $this->stats['officers_skipped']],
                ['Beneficiaries Processed', $this->stats['beneficiaries_processed']],
                ['Beneficiaries Created', $this->stats['beneficiaries_created']],
                ['Beneficiaries Skipped', $this->stats['beneficiaries_skipped']],
                ['Errors', count($this->stats['errors'])],
            ]
        );

        if (!empty($this->stats['errors'])) {
            $this->newLine();
            $this->warn('Errors encountered:');
            
            // Group errors by type for better readability
            $errorTypes = [];
            $hasConnectionError = false;
            foreach ($this->stats['errors'] as $error) {
                if (str_contains($error, 'Officer not found')) {
                    $errorTypes['officer_not_found'] = ($errorTypes['officer_not_found'] ?? 0) + 1;
                } elseif (str_contains($error, 'Database connection') || str_contains($error, 'Connection refused')) {
                    $errorTypes['database_connection'] = ($errorTypes['database_connection'] ?? 0) + 1;
                    $hasConnectionError = true;
                } else {
                    $errorTypes['other'] = ($errorTypes['other'] ?? 0) + 1;
                }
            }
            
            // Show error type summary
            if (isset($errorTypes['database_connection'])) {
                $this->newLine();
                $this->error("  ⚠ Database Connection Errors: {$errorTypes['database_connection']}");
                $this->warn("     All operations failed due to database connection issues.");
                $this->warn("     Please check your database server and connection settings.");
            }
            if (isset($errorTypes['officer_not_found'])) {
                $this->warn("  - Officer not found errors: {$errorTypes['officer_not_found']}");
            }
            if (isset($errorTypes['other'])) {
                $this->warn("  - Other errors: {$errorTypes['other']}");
            }
            
            // Show first 10 unique errors (skip if all are connection errors)
            if (!$hasConnectionError || count($this->stats['errors']) < 20) {
                $uniqueErrors = array_unique(array_slice($this->stats['errors'], 0, 50));
                foreach (array_slice($uniqueErrors, 0, 10) as $error) {
                    $this->error("  " . $error);
                }
                
                if (count($this->stats['errors']) > 10) {
                    $this->warn('  ... and ' . (count($this->stats['errors']) - 10) . ' more errors (use --verbose for details)');
                }
            }
        }
    }

    private function displayMissingOfficersReport(): void
    {
        $this->newLine();
        $this->info('=== Missing Officers Report ===');
        $this->warn('The following officer membership numbers are referenced by beneficiaries but were not found in the officers CSV:');
        $this->newLine();
        
        // Sort by number of beneficiaries referencing each missing officer
        arsort($this->missingOfficers);
        
        // Show top 20 most referenced missing officers
        $topMissing = array_slice($this->missingOfficers, 0, 20, true);
        
        $rows = [];
        foreach ($topMissing as $membershipNumber => $count) {
            $rows[] = [$membershipNumber, $count];
        }
        
        $this->table(
            ['Membership Number', 'Beneficiaries Referencing'],
            $rows
        );
        
        if (count($this->missingOfficers) > 20) {
            $this->warn('... and ' . (count($this->missingOfficers) - 20) . ' more missing officers');
        }
        
        $this->newLine();
        $this->info('Total unique missing officers: ' . count($this->missingOfficers));
        $this->info('Total beneficiaries affected: ' . array_sum($this->missingOfficers));
        $this->newLine();
        $this->warn('Possible solutions:');
        $this->warn('  1. Check if there are additional officer CSV files that need to be processed');
        $this->warn('  2. Verify the membership numbers in the beneficiaries CSV are correct');
        $this->warn('  3. These beneficiaries can be migrated later when their officers are added');
    }
}
