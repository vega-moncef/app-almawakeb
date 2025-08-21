<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_visit_id',
        'test_id',
        'status',
        'assigned_at',
        'started_at',
        'completed_at',
        'total_score',
        'percentage',
        'passed',
        'admission_decision',
        'notes'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean'
    ];

    // Relationships
    public function studentVisit()
    {
        return $this->belongsTo(StudentVisit::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function results()
    {
        return $this->hasMany(StudentTestResult::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAdmissionDecision($query, $decision)
    {
        return $query->where('admission_decision', $decision);
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    // Helper methods
    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'assigned' => 'Assigné',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'absent' => 'Absent'
        ];

        return $statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $statusColors = [
            'assigned' => 'info',
            'in_progress' => 'warning',
            'completed' => 'success',
            'absent' => 'danger'
        ];

        return $statusColors[$this->status] ?? 'secondary';
    }

    public function getAdmissionDecisionLabelAttribute()
    {
        $decisionLabels = [
            'pending' => 'En attente',
            'accepted' => 'Accepté',
            'rejected' => 'Refusé'
        ];

        return $decisionLabels[$this->admission_decision] ?? $this->admission_decision;
    }

    public function getAdmissionDecisionColorAttribute()
    {
        $decisionColors = [
            'pending' => 'warning',
            'accepted' => 'success',
            'rejected' => 'danger'
        ];

        return $decisionColors[$this->admission_decision] ?? 'secondary';
    }
}
