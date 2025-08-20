<?php
// app/Models/ClassRoom.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'level_id',
        'academic_year_id', 
        'name', 
        'full_name', 
        'capacity', 
        'current_students', 
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Helper methods
    public function hasCapacity()
    {
        return $this->current_students < $this->capacity;
    }

    public function getAvailableSpotsAttribute()
    {
        return $this->capacity - $this->current_students;
    }

    public function getOccupancyPercentageAttribute()
    {
        return $this->capacity > 0 ? round(($this->current_students / $this->capacity) * 100, 1) : 0;
    }

    public function getStatusAttribute()
    {
        $percentage = $this->occupancy_percentage;
        
        if ($percentage >= 100) {
            return 'full';
        } elseif ($percentage >= 80) {
            return 'almost_full';
        } elseif ($percentage >= 50) {
            return 'half_full';
        } else {
            return 'available';
        }
    }

    // Scopes
    public function scopeForCurrentYear($query)
    {
        $currentYear = AcademicYear::current();
        return $query->where('academic_year_id', $currentYear?->id);
    }

    public function scopeForYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeAvailable($query)
    {
        return $query->whereRaw('current_students < capacity');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}