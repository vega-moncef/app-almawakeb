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
        'test_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'total_marks',
        'passing_marks',
        'is_active',
        'instructions'
    ];

    protected $casts = [
        'test_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
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
    public function getFormattedDateAttribute()
    {
        return $this->test_date ? $this->test_date->format('d/m/Y') : '';
    }

    public function getFormattedTimeAttribute()
    {
        return $this->start_time && $this->end_time 
            ? $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i')
            : '';
    }

    public function getTypeColorAttribute()
    {
        return $this->type === 'ORAL' ? 'primary' : 'success';
    }

    public function getStatusColorAttribute()
    {
        if (!$this->is_active) return 'secondary';
        if ($this->test_date->isPast()) return 'warning';
        return 'success';
    }
}
