<?php
// database/seeders/LevelSeeder.php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run()
    {
        $schools = School::all();
        $academicYears = AcademicYear::all();

        foreach ($academicYears as $academicYear) {
            foreach ($schools as $school) {
                $this->createLevelsForSchool($school, $academicYear);
            }
        }
    }

    private function createLevelsForSchool($school, $academicYear)
    {
        switch ($school->code) {
            case 'PRI':
                $this->createPrimaryLevels($school, $academicYear);
                break;
            case 'COL':
                $this->createCollegeLevels($school, $academicYear);
                break;
            case 'LYC':
                $this->createLyceeLevels($school, $academicYear);
                break;
        }
    }

    private function createPrimaryLevels($school, $academicYear)
    {
        $levels = [
            ['name' => '1ère année', 'code' => '1_PRI', 'order' => 1],
            ['name' => '2ème année', 'code' => '2_PRI', 'order' => 2],
            ['name' => '3ème année', 'code' => '3_PRI', 'order' => 3],
            ['name' => '4ème année', 'code' => '4_PRI', 'order' => 4],
            ['name' => '5ème année', 'code' => '5_PRI', 'order' => 5],
            ['name' => '6ème année', 'code' => '6_PRI', 'order' => 6],
        ];

        foreach ($levels as $level) {
            Level::create([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'name' => $level['name'],
                'code' => $level['code'],
                'order' => $level['order']
            ]);
        }
    }

    private function createCollegeLevels($school, $academicYear)
    {
        $levels = [
            ['name' => '1APIC', 'code' => '1APIC', 'order' => 1],
            ['name' => '2APIC', 'code' => '2APIC', 'order' => 2],
            ['name' => '3APIC', 'code' => '3APIC', 'order' => 3],
        ];

        foreach ($levels as $level) {
            Level::create([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'name' => $level['name'],
                'code' => $level['code'],
                'order' => $level['order']
            ]);
        }
    }

    private function createLyceeLevels($school, $academicYear)
    {
        $levels = [
            ['name' => 'TC', 'code' => 'TC', 'order' => 1],
            ['name' => '1BAC SM', 'code' => '1BAC_SM', 'order' => 2],
            ['name' => '1BAC S Physique', 'code' => '1BAC_SP', 'order' => 3],
            ['name' => '2BAC SM', 'code' => '2BAC_SM', 'order' => 4],
            ['name' => '2BAC S Physique', 'code' => '2BAC_SP', 'order' => 5],
        ];

        foreach ($levels as $level) {
            Level::create([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'name' => $level['name'],
                'code' => $level['code'],
                'order' => $level['order']
            ]);
        }
    }
}