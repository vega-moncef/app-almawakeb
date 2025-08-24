<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Arabe', 'code' => 'ARB', 'description' => 'Langue arabe', 'order' => 1],
            ['name' => 'Français', 'code' => 'FRA', 'description' => 'Langue française', 'order' => 2],
            ['name' => 'Mathématiques', 'code' => 'MATH', 'description' => 'Mathématiques', 'order' => 3],
            ['name' => 'Sciences', 'code' => 'SCI', 'description' => 'Sciences physiques et naturelles', 'order' => 4],
            ['name' => 'Histoire-Géographie', 'code' => 'HG', 'description' => 'Histoire et géographie', 'order' => 5],
            ['name' => 'Anglais', 'code' => 'ENG', 'description' => 'Langue anglaise', 'order' => 6],
            ['name' => 'Éducation Islamique', 'code' => 'EI', 'description' => 'Éducation islamique', 'order' => 7],
            ['name' => 'Physique-Chimie', 'code' => 'PC', 'description' => 'Physique et chimie', 'order' => 8],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'description' => 'Biologie et géologie', 'order' => 9],
            ['name' => 'Philosophie', 'code' => 'PHILO', 'description' => 'Philosophie', 'order' => 10],
            ['name' => 'Économie', 'code' => 'ECO', 'description' => 'Sciences économiques', 'order' => 11],
            ['name' => 'Comptabilité', 'code' => 'COMPTA', 'description' => 'Comptabilité', 'order' => 12],
            ['name' => 'Informatique et Robotique', 'code' => 'INFO', 'description' => 'Informatique et robotique', 'order' => 13],
            ['name' => 'Art Plastique', 'code' => 'ART', 'description' => 'Arts plastiques et visuels', 'order' => 14],
            ['name' => 'Sport', 'code' => 'SPORT', 'description' => 'Éducation physique et sportive', 'order' => 15],
            ['name' => 'Danse', 'code' => 'DANCE', 'description' => 'Danse et expression corporelle', 'order' => 16]
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate([
                'code' => $subject['code']
            ], [
                'name' => $subject['name'],
                'description' => $subject['description'],
                'order' => $subject['order'],
                'is_active' => true
            ]);
        }
    }
}
