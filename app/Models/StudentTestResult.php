<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_test_id',
        'subject_id',
        'score',
        'max_score',
        'remarks'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2'
    ];

    // Relationships
    public function studentTest()
    {
        return $this->belongsTo(StudentTest::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Helper methods
    public function getPercentageAttribute()
    {
        if ($this->max_score == 0) return 0;
        return round(($this->score / $this->max_score) * 100, 2);
    }

    public function getGradeAttribute()
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }

    public function getGradeColorAttribute()
    {
        $grade = $this->grade;
        
        switch ($grade) {
            case 'A+':
            case 'A':
                return 'success';
            case 'B':
                return 'primary';
            case 'C':
                return 'warning';
            case 'D':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}
