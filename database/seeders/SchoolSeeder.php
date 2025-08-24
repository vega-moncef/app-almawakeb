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
                'name' => 'Maternelle',
                'code' => 'MAT',
                'description' => 'École Maternelle - Petite, Moyenne et Grande Section'
            ],
            [
                'name' => 'Primaire',
                'code' => 'PRI',
                'description' => 'École Primaire - CP, CE1, CE2, CM1, CM2'
            ],
            [
                'name' => 'Collège',
                'code' => 'COL',
                'description' => 'Collège - 6ème, 5ème, 4ème, 3ème'
            ],
            [
                'name' => 'Lycée',
                'code' => 'LYC',
                'description' => 'Lycée - Seconde, Première, Terminale'
            ]
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}