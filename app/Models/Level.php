<?php
// app/Models/Level.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 
        'academic_year_id',
        'name', 
        'code', 
        'order', 
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function studentVisits()
    {
        return $this->hasMany(StudentVisit::class, 'requested_level_id');
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->school->name . ' - ' . $this->name . ' (' . $this->academicYear->name . ')';
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

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}