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
            case 'MAT':
                $this->createMaternelleLevels($school, $academicYear);
                break;
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

    private function createMaternelleLevels($school, $academicYear)
    {
        $levels = [
            ['name' => 'Petite Section', 'code' => 'PS', 'order' => 1],
            ['name' => 'Moyenne Section', 'code' => 'MS', 'order' => 2],
            ['name' => 'Grande Section', 'code' => 'GS', 'order' => 3],
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

    private function createPrimaryLevels($school, $academicYear)
    {
        $levels = [
            ['name' => 'CP', 'code' => 'CP', 'order' => 1],
            ['name' => 'CE1', 'code' => 'CE1', 'order' => 2],
            ['name' => 'CE2', 'code' => 'CE2', 'order' => 3],
            ['name' => 'CM1', 'code' => 'CM1', 'order' => 4],
            ['name' => 'CM2', 'code' => 'CM2', 'order' => 5],
            ['name' => 'CE6', 'code' => 'CE6', 'order' => 6],
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
            ['name' => '1BAC', 'code' => '1BAC', 'order' => 2],
            ['name' => '2BAC', 'code' => '2BAC', 'order' => 3],
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