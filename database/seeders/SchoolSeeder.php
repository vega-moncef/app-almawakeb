<?php
// database/seeders/SchoolSeeder.php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run()
    {
        $schools = [
            [
                'name' => 'Primary',
                'code' => 'PRI',
                'description' => 'Primary School - Grades 1 to 6'
            ],
            [
                'name' => 'College',
                'code' => 'COL',
                'description' => 'College School - 1APIC to 3APIC'
            ],
            [
                'name' => 'Lycee',
                'code' => 'LYC',
                'description' => 'Lycee School - TC to 2BAC'
            ]
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}