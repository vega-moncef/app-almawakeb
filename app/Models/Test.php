<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'title',
        'description',
        'type',
        'test_file',
        'duration_minutes',
        'total_marks',
        'passing_marks',
        'is_active',
        'instructions'
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'test_subjects')
                    ->withPivot('marks', 'order')
                    ->withTimestamps()
                    ->orderBy('test_subjects.order');
    }

    public function studentTests()
    {
        return $this->hasMany(StudentTest::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCurrentYear($query)
    {
        $currentYear = AcademicYear::current();
        return $query->where('academic_year_id', $currentYear?->id);
    }

    public function scopeForLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods

    public function getTypeColorAttribute()
    {
        return $this->type === 'ORAL' ? 'primary' : 'success';
    }

    public function getStatusColorAttribute()
    {
        if (!$this->is_active) return 'secondary';
        return 'success';
    }
}
