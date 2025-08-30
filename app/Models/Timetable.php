<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'teacher_id',
        'subject_id',
        'time_slot_id',
        'day_of_week', // 1=Monday, 2=Tuesday, ..., 5=Friday
        'is_active'
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'day_name'
    ];

    // Constants for days
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;

    public static function getDays()
    {
        return [
            self::MONDAY => 'Lundi',
            self::TUESDAY => 'Mardi', 
            self::WEDNESDAY => 'Mercredi',
            self::THURSDAY => 'Jeudi',
            self::FRIDAY => 'Vendredi'
        ];
    }

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    // Accessors
    public function getDayNameAttribute()
    {
        return self::getDays()[$this->day_of_week] ?? '';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeForTimeSlot($query, $timeSlotId)
    {
        return $query->where('time_slot_id', $timeSlotId);
    }

    // Helper methods
    public static function hasConflict($teacherId, $classId, $timeSlotId, $dayOfWeek, $academicYearId, $excludeId = null)
    {
        $query = self::where('academic_year_id', $academicYearId)
                     ->where('day_of_week', $dayOfWeek)
                     ->where('time_slot_id', $timeSlotId)
                     ->where('is_active', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check teacher conflict
        $teacherConflict = (clone $query)->where('teacher_id', $teacherId)->exists();
        
        // Check class conflict
        $classConflict = (clone $query)->where('class_id', $classId)->exists();

        return $teacherConflict || $classConflict;
    }

    public function hasConflicts()
    {
        return self::hasConflict(
            $this->teacher_id,
            $this->class_id,
            $this->time_slot_id,
            $this->day_of_week,
            $this->academic_year_id,
            $this->id
        );
    }
}
