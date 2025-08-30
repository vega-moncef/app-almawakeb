<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TimeSlot;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeSlots = [
            [
                'name' => '8h30-9h25',
                'start_time' => '08:30',
                'end_time' => '09:25',
                'order' => 1,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '9h25-10h20',
                'start_time' => '09:25',
                'end_time' => '10:20',
                'order' => 2,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '10h20-10h40',
                'start_time' => '10:20',
                'end_time' => '10:40',
                'order' => 3,
                'is_active' => true,
                'is_break' => true
            ],
            [
                'name' => '10h40-11h25',
                'start_time' => '10:40',
                'end_time' => '11:25',
                'order' => 4,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '11h25-12h30',
                'start_time' => '11:25',
                'end_time' => '12:30',
                'order' => 5,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '12h30-13h15',
                'start_time' => '12:30',
                'end_time' => '13:15',
                'order' => 6,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '13h15-14h10',
                'start_time' => '13:15',
                'end_time' => '14:10',
                'order' => 7,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '14h10-15h05',
                'start_time' => '14:10',
                'end_time' => '15:05',
                'order' => 8,
                'is_active' => true,
                'is_break' => false
            ],
            [
                'name' => '15h05-16h00',
                'start_time' => '15:05',
                'end_time' => '16:00',
                'order' => 9,
                'is_active' => true,
                'is_break' => false
            ]
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::updateOrCreate(
                ['name' => $slot['name']],
                $slot
            );
        }
    }
}
