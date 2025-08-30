<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'order',
        'is_active',
        'is_break',
        'break_type',
        'school_id',
        'level_id',
        'class_id'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'order' => 'integer',
        'is_active' => 'boolean',
        'is_break' => 'boolean'
    ];

    protected $appends = [
        'display_time',
        'duration_in_minutes',
        'scope_display'
    ];

    // Relationships
    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
    
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    // Accessors
    public function getDisplayTimeAttribute()
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    public function getDurationInMinutesAttribute()
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeRegularClasses($query)
    {
        return $query->where('is_break', false);
    }

    public function scopeBreaks($query)
    {
        return $query->where('is_break', true);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where(function($q) use ($schoolId) {
            $q->where('school_id', $schoolId)->orWhereNull('school_id');
        });
    }

    public function scopeForLevel($query, $levelId)
    {
        return $query->where(function($q) use ($levelId) {
            $q->where('level_id', $levelId)->orWhereNull('level_id');
        });
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where(function($q) use ($classId) {
            $q->where('class_id', $classId)->orWhereNull('class_id');
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('school_id')
                    ->whereNull('level_id')
                    ->whereNull('class_id');
    }

    // Helper methods
    public function conflictsWith(TimeSlot $other)
    {
        // Only check time conflicts if they're in the same scope
        if (!$this->isSameScope($other)) {
            return false;
        }
        
        return !($this->end_time <= $other->start_time || $this->start_time >= $other->end_time);
    }

    public function isSameScope(TimeSlot $other)
    {
        return $this->school_id === $other->school_id &&
               $this->level_id === $other->level_id &&
               $this->class_id === $other->class_id;
    }

    public function isGlobal()
    {
        return is_null($this->school_id) && is_null($this->level_id) && is_null($this->class_id);
    }

    public function getScopeDisplayAttribute()
    {
        $scopes = [];
        
        if ($this->classRoom) {
            $levelName = $this->classRoom->level ? $this->classRoom->level->name . ' ' : '';
            $scopes[] = "Classe: {$levelName}{$this->classRoom->name}";
        } elseif ($this->level_id && $this->level) {
            $scopes[] = "Niveau: {$this->level->name}";
        } elseif ($this->school_id && $this->school) {
            $scopes[] = "École: {$this->school->name}";
        } else {
            $scopes[] = "Global";
        }
        
        return implode(' - ', $scopes);
    }
}
