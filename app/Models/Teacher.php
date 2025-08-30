<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name', 
        'phone',
        'email',
        'address',
        'date_of_birth',
        'hire_date',
        'type', // 'permanent' or 'vacataire'
        'qualification',
        'experience_years',
        'is_active',
        'photo',
        // New fields
        'user_id',
        'employee_id',
        'school_id',
        'level_id',
        'main_subject_id', // Subject ID
        'qualification_level',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'max_hours_per_week',
        'salary_type',
        'hourly_rate',
        'monthly_salary',
        'has_account',
        'account_created_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'experience_years' => 'integer',
        // New field casts
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'max_hours_per_week' => 'integer',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'has_account' => 'boolean',
        'account_created_at' => 'datetime'
    ];

    protected $appends = [
        'type_display',
        'full_name',
        'photo_url'
    ];

    // Constants for teacher types
    const TYPE_PERMANENT = 'permanent';
    const TYPE_VACATAIRE = 'vacataire';

    // Constants for contract types
    const CONTRACT_CDI = 'cdi';
    const CONTRACT_CDD = 'cdd';
    const CONTRACT_INTERIM = 'interim';
    const CONTRACT_STAGE = 'stage';

    // Constants for salary types
    const SALARY_MONTHLY = 'monthly';
    const SALARY_HOURLY = 'hourly';

    public static function getTypes()
    {
        return [
            self::TYPE_PERMANENT => 'Permanent',
            self::TYPE_VACATAIRE => 'Vacataire'
        ];
    }

    public static function getContractTypes()
    {
        return [
            self::CONTRACT_CDI => 'CDI',
            self::CONTRACT_CDD => 'CDD',
            self::CONTRACT_INTERIM => 'Intérim',
            self::CONTRACT_STAGE => 'Stage'
        ];
    }

    public static function getSalaryTypes()
    {
        return [
            self::SALARY_MONTHLY => 'Mensuel',
            self::SALARY_HOURLY => 'Horaire'
        ];
    }

    // Relationships
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')
                    ->withTimestamps();
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function classAssignments()
    {
        return $this->belongsToMany(ClassRoom::class, 'teacher_class_assignments', 'teacher_id', 'class_id')
                    ->withPivot('subject_id', 'academic_year_id')
                    ->withTimestamps();
    }

    // New relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function mainSubject()
    {
        return $this->belongsTo(Subject::class, 'main_subject_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getTypeDisplayAttribute()
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : asset('assets/images/users/dummy-avatar.jpg');
    }

    public function getContractTypeDisplayAttribute()
    {
        return self::getContractTypes()[$this->contract_type] ?? $this->contract_type;
    }

    public function getSalaryTypeDisplayAttribute()
    {
        return self::getSalaryTypes()[$this->salary_type] ?? $this->salary_type;
    }

    public function getSalaryDisplayAttribute()
    {
        if ($this->salary_type === self::SALARY_HOURLY && $this->hourly_rate) {
            return $this->hourly_rate . ' DH/h';
        } elseif ($this->salary_type === self::SALARY_MONTHLY && $this->monthly_salary) {
            return number_format($this->monthly_salary, 2) . ' DH/mois';
        }
        return '-';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePermanent($query)
    {
        return $query->where('type', self::TYPE_PERMANENT);
    }

    public function scopeVacataire($query)
    {
        return $query->where('type', self::TYPE_VACATAIRE);
    }


    // Helper methods
    public function canTeachSubject($subjectId)
    {
        return $this->subjects()->where('subject_id', $subjectId)->exists();
    }

    public function getScheduleForDay($day, $academicYearId = null)
    {
        $query = $this->timetables()->where('day_of_week', $day);
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        return $query->with(['timeSlot', 'subject', 'classRoom'])->get();
    }

    public function hasConflictAt($dayOfWeek, $timeSlotId, $academicYearId, $excludeId = null)
    {
        $query = $this->timetables()
                      ->where('day_of_week', $dayOfWeek)
                      ->where('time_slot_id', $timeSlotId)
                      ->where('academic_year_id', $academicYearId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}