<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DEPRECATED: This seeder is deprecated in favor of LocationSeeder.
 *
 * The old SQL-based approach has been replaced with a CSV-based seeder
 * for better performance and database compatibility.
 *
 * @deprecated Use LocationSeeder instead
 * @see LocationSeeder
 */
class SqlFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->warn('⚠️  SqlFileSeeder is deprecated. Using LocationSeeder instead...');
        $this->call(LocationSeeder::class);
    }
}
