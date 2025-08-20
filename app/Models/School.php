<?php
// app/Models/School.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'code', 
        'description', 
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function academicYears()
    {
        return $this->belongsToMany(AcademicYear::class)->withTimestamps();
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    public function studentVisits()
    {
        return $this->hasMany(StudentVisit::class, 'requested_school_id');
    }

    // Helper methods to get levels for specific academic year
    public function levelsForYear($academicYearId)
    {
        return $this->levels()->where('academic_year_id', $academicYearId);
    }

    public function levelsForCurrentYear()
    {
        $currentYear = AcademicYear::current();
        return $currentYear ? $this->levelsForYear($currentYear->id) : collect();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}