<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcademicYearSeeder::class,  // First: Create academic years
            SchoolSeeder::class,        // Second: Create schools
            LevelSeeder::class,         // Third: Create levels (depends on schools and academic years)
        ]);

        $this->command->info('✅ Basic school structure seeded successfully!');
        $this->command->info('📅 Academic years: 2022-2023 to 2026-2027');
        $this->command->info('🏫 Schools: Primary, College, Lycee');
        $this->command->info('📚 Levels created for all years');
        $this->command->info('🎯 Current academic year: 2024-2025');
    }
}