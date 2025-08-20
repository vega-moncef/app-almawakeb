<?php
// app/Models/AcademicYear.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
        'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function schools()
    {
        return $this->belongsToMany(School::class)->withTimestamps();
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function studentVisits()
    {
        return $this->hasMany(StudentVisit::class);
    }

    // Helper methods
    public static function current()
    {
        return self::where('is_current', true)->first();
    }

    public static function generateName($startYear = null)
    {
        $year = $startYear ?? now()->year;
        
        // If we're after June, start next academic year
        if (now()->month >= 7) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    public function setAsCurrent()
    {
        // Remove current flag from all years
        self::where('is_current', true)->update(['is_current' => false]);
        
        // Set this year as current
        $this->update(['is_current' => true]);
    }

    public function isActive()
    {
        $now = now()->toDateString();
        return $now >= $this->start_date && $now <= $this->end_date;
    }

    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->start_date->format('M Y') . ' - ' . $this->end_date->format('M Y') . ')';
    }
}