<?php
// app/Models/StudentVisit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'student_first_name', 
        'student_last_name', 
        'student_gender',
        'birth_date', 
        'birth_place', 
        'current_school', 
        'current_level',
        'city', 
        'repeat_count', 
        'father_first_name', 
        'father_last_name',
        'father_phone', 
        'father_email', 
        'father_profession',
        'mother_first_name', 
        'mother_last_name', 
        'mother_phone',
        'mother_email', 
        'mother_profession', 
        'observations',
        'visit_date', 
        'test_date', 
        'status', 
        'requested_school_id',
        'requested_level_id', 
        'student_file'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'visit_date' => 'datetime',
        'test_date' => 'datetime',
        'repeat_count' => 'integer',
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function requestedSchool()
    {
        return $this->belongsTo(School::class, 'requested_school_id');
    }

    public function requestedLevel()
    {
        return $this->belongsTo(Level::class, 'requested_level_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->student_first_name . ' ' . $this->student_last_name;
    }

    public function getFatherFullNameAttribute()
    {
        return $this->father_first_name . ' ' . $this->father_last_name;
    }

    public function getMotherFullNameAttribute()
    {
        return $this->mother_first_name . ' ' . $this->mother_last_name;
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'pending' => 'En attente',
            'test_scheduled' => 'Test programmé',
            'tested' => 'Testé',
            'accepted' => 'Accepté',
            'rejected' => 'Refusé'
        ];

        return $statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $statusColors = [
            'pending' => 'warning',
            'test_scheduled' => 'info',
            'tested' => 'secondary',
            'accepted' => 'success',
            'rejected' => 'danger'
        ];

        return $statusColors[$this->status] ?? 'secondary';
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

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('requested_school_id', $schoolId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('visit_date', 'desc');
    }

    // Automatically set academic year when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($studentVisit) {
            if (!$studentVisit->academic_year_id) {
                $currentYear = AcademicYear::current();
                if ($currentYear) {
                    $studentVisit->academic_year_id = $currentYear->id;
                }
            }
        });
    }
}