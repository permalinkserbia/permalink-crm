<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Address;

class ImportLeadsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:import-csv {file=solidus - solidus.csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import leads from CSV file located in public folder';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filename = $this->argument('file');
        $filePath = public_path($filename);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading CSV file: {$filename}");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->error("Could not read CSV headers");
            fclose($handle);
            return Command::FAILURE;
        }

        // Normalize headers (remove BOM if present, trim whitespace)
        $headers = array_map(function($header) {
            return trim(str_replace("\xEF\xBB\xBF", '', $header));
        }, $headers);

        $this->info("CSV Headers: " . implode(', ', $headers));

        $rowCount = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Get the first user for user_created_id and user_owner_id
        $firstUser = \App\Models\User::first();
        if (!$firstUser) {
            $this->error("No users found in database. Please create a user first.");
            fclose($handle);
            return Command::FAILURE;
        }

        // Ensure lead_prefix setting exists (required by LeadObserver)
        $tablePrefix = config('laravel-crm.db_table_prefix');
        $settingExists = DB::table($tablePrefix . 'settings')
            ->where('name', 'lead_prefix')
            ->exists();

        if (!$settingExists) {
            $this->info("Creating lead_prefix setting...");
            DB::table($tablePrefix . 'settings')->insert([
                'name' => 'lead_prefix',
                'value' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map CSV row to associative array
                // Check if row has enough columns
                if (count($row) < count($headers)) {
                    // Pad row with empty strings if it's shorter than headers
                    $row = array_pad($row, count($headers), '');
                } elseif (count($row) > count($headers)) {
                    // Truncate row if it's longer than headers
                    $row = array_slice($row, 0, count($headers));
                }

                $data = array_combine($headers, $row);

                // Validate that array_combine worked
                if ($data === false) {
                    $errors[] = "Row {$rowCount}: Failed to map CSV columns";
                    $errorCount++;
                    continue;
                }

                // Skip if required fields are missing
                if (empty($data['Ime'] ?? '') && empty($data['Email'] ?? '')) {
                    $errors[] = "Row {$rowCount}: Missing both Ime and Email";
                    $errorCount++;
                    continue;
                }

                try {
                    // Extract data
                    $firstName = trim($data['Ime'] ?? '');
                    $lastName = trim($data['Prezime'] ?? '');
                    $city = trim($data['Mesto'] ?? '');
                    $phone = trim($data['Telefon'] ?? '');
                    $email = trim($data['Email'] ?? '');
                    $status = trim($data['Status'] ?? '');

                    // Find or create Person
                    // Note: We can't query encrypted email fields directly, so we only search by name
                    $person = null;

                    // Try to find by first_name and last_name
                    if (!empty($firstName) && !empty($lastName)) {
                        $person = Person::where('first_name', $firstName)
                            ->where('last_name', $lastName)
                            ->first();
                    } elseif (!empty($firstName)) {
                        // If only first name is available, try to find by first name only
                        $person = Person::where('first_name', $firstName)->first();
                    }

                    if (!$person) {
                        // Create new Person
                        $person = Person::create([
                            'external_id' => Str::uuid()->toString(),
                            'first_name' => $firstName ?: 'Unknown',
                            'last_name' => $lastName,
                            'user_created_id' => $firstUser->id,
                            'user_owner_id' => $firstUser->id,
                        ]);

                        // Create Email for Person if provided
                        if (!empty($email)) {
                            Email::create([
                                'external_id' => Str::uuid()->toString(),
                                'address' => $email,
                                'primary' => true,
                                'type' => 'work',
                                'emailable_type' => Person::class,
                                'emailable_id' => $person->id,
                                'user_created_id' => $firstUser->id,
                            ]);
                        }

                        // Create Phone for Person if provided
                        if (!empty($phone)) {
                            // Clean phone number (remove spaces, slashes, etc.)
                            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                            if (!empty($cleanPhone)) {
                                Phone::create([
                                    'external_id' => Str::uuid()->toString(),
                                    'number' => $cleanPhone,
                                    'primary' => true,
                                    'type' => 'mobile',
                                    'phoneable_type' => Person::class,
                                    'phoneable_id' => $person->id,
                                    'user_created_id' => $firstUser->id,
                                ]);
                            }
                        }

                        // Create Address for Person if city is provided
                        if (!empty($city)) {
                            Address::create([
                                'external_id' => Str::uuid()->toString(),
                                'city' => $city,
                                'primary' => true,
                                'addressable_type' => Person::class,
                                'addressable_id' => $person->id,
                                'user_created_id' => $firstUser->id,
                            ]);
                        }
                    } else {
                        // Update existing person if needed
                        if ($person && $person->id) {
                            try {
                                $updated = false;
                                // Safely get last_name attribute
                                $personLastName = '';
                                try {
                                    $personLastName = $person->last_name ?? '';
                                } catch (\Exception $e) {
                                    // If decryption fails, treat as empty
                                    $personLastName = '';
                                }

                                if (empty($personLastName) && !empty($lastName)) {
                                    $person->last_name = $lastName;
                                    $updated = true;
                                }
                                if ($updated) {
                                    $person->save();
                                }
                            } catch (\Exception $e) {
                                // If we can't update person, continue anyway
                                $this->warn("Could not update person for row {$rowCount}: " . $e->getMessage());
                            }
                        }

                        // Add phone if doesn't exist
                        if (!empty($phone)) {
                            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                            if (!empty($cleanPhone)) {
                                $existingPhone = Phone::where('phoneable_type', Person::class)
                                    ->where('phoneable_id', $person->id)
                                    ->where('number', $cleanPhone)
                                    ->first();

                                if (!$existingPhone) {
                                    Phone::create([
                                        'external_id' => Str::uuid()->toString(),
                                        'number' => $cleanPhone,
                                        'primary' => false,
                                        'type' => 'mobile',
                                        'phoneable_type' => Person::class,
                                        'phoneable_id' => $person->id,
                                        'user_created_id' => $firstUser->id,
                                    ]);
                                }
                            }
                        }
                    }

                    // Create Lead - ensure person exists
                    if (!$person || !$person->id) {
                        throw new \Exception("Failed to create or retrieve person for row");
                    }

                    $leadTitle = trim(($firstName . ' ' . $lastName)) ?: 'Lead from CSV';

                    Lead::create([
                        'external_id' => Str::uuid()->toString(),
                        'person_id' => $person->id,
                        'title' => $leadTitle,
                        'description' => !empty($status) ? "Status: {$status}" : null,
                        'user_created_id' => $firstUser->id,
                        'user_owner_id' => $firstUser->id,
                    ]);

                    $successCount++;

                    if ($rowCount % 100 == 0) {
                        $this->info("Processed {$rowCount} rows...");
                    }

                } catch (\Exception $e) {
                    $errorMessage = "Row {$rowCount}: " . $e->getMessage();
                    if ($e->getFile() && $e->getLine()) {
                        $errorMessage .= " (in {$e->getFile()} on line {$e->getLine()})";
                    }
                    $errors[] = $errorMessage;
                    $errorCount++;

                    // Show detailed error for first few errors
                    if ($errorCount <= 3) {
                        $this->error("Error processing row {$rowCount}: " . $e->getMessage());
                        $this->error("File: {$e->getFile()}");
                        $this->error("Line: {$e->getLine()}");
                        $this->error("Trace: " . $e->getTraceAsString());
                    } else {
                        $this->warn("Error processing row {$rowCount}: " . $e->getMessage());
                    }

                    // If we have too many errors, stop processing
                    if ($errorCount > 1000) {
                        $this->error("Too many errors encountered. Stopping import.");
                        break;
                    }
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("\nImport completed!");
            $this->info("Total rows processed: {$rowCount}");
            $this->info("Successfully imported: {$successCount}");
            $this->info("Errors: {$errorCount}");

            if (!empty($errors) && $this->option('verbose')) {
                $this->warn("\nErrors encountered:");
                foreach ($errors as $error) {
                    $this->warn("  - {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Fatal error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
