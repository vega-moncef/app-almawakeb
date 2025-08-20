<?php
// database/seeders/AcademicYearSeeder.php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AcademicYearSeeder extends Seeder
{
    public function run()
    {
        // Create academic years for multiple years
        $years = [
            ['start' => 2022, 'current' => false],
            ['start' => 2023, 'current' => false],
            ['start' => 2024, 'current' => true],  // Current year
            ['start' => 2025, 'current' => false],
            ['start' => 2026, 'current' => false],
        ];

        foreach ($years as $yearData) {
            $startYear = $yearData['start'];
            $endYear = $startYear + 1;

            AcademicYear::create([
                'name' => $startYear . '-' . $endYear,
                'start_date' => Carbon::create($startYear, 9, 1), // September 1st
                'end_date' => Carbon::create($endYear, 6, 30), // June 30th
                'is_current' => $yearData['current'],
                'is_active' => true,
                'description' => 'Academic Year ' . $startYear . '-' . $endYear
            ]);
        }
    }
}