<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if locations already seeded
        $existingCount = Location::count();

        if ($existingCount > 41000) {
            $this->command->info("✓ Locations already seeded ({$existingCount} records), skipping...");

            return;
        }

        $csvPath = database_path('seeders/data/locations.csv');

        if (! File::exists($csvPath)) {
            $this->command->error("❌ CSV file not found: {$csvPath}");
            $this->command->error('Please run: python3 scripts/convert_sql_to_csv.py');

            return;
        }

        $this->command->info('Seeding locations from CSV...');

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error('❌ Failed to open CSV file');

            return;
        }

        // Skip header row
        fgetcsv($handle);

        $batch = [];
        $batchSize = 1000;
        $totalInserted = 0;
        $startTime = microtime(true);

        while (($row = fgetcsv($handle)) !== false) {
            // Skip invalid rows
            if (count($row) < 8) {
                continue;
            }

            $batch[] = [
                'id' => (int) $row[0],
                'city' => $row[1],
                'state_code' => $row[2],
                'state' => $row[3] ?: null,
                'zip' => $row[4],
                'latitude' => (float) $row[5],
                'longitude' => (float) $row[6],
                'county' => $row[7],
                'timezone' => $row[8] ?? null,
            ];

            // Insert in batches for performance
            if (count($batch) >= $batchSize) {
                DB::table('locations')->insert($batch);
                $totalInserted += count($batch);
                $this->command->info("  Inserted {$totalInserted} locations...");
                $batch = [];
            }
        }

        // Insert remaining records
        if (! empty($batch)) {
            DB::table('locations')->insert($batch);
            $totalInserted += count($batch);
        }

        fclose($handle);

        $duration = round(microtime(true) - $startTime, 2);

        $this->command->info("✓ Successfully seeded {$totalInserted} locations in {$duration}s");

        // Update the sequence for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            $maxId = DB::table('locations')->max('id');
            DB::statement("SELECT setval('locations_id_seq', {$maxId})");
            $this->command->info("✓ Updated PostgreSQL sequence to {$maxId}");
        }
    }
}
