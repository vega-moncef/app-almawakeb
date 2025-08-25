<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'school_id',
        'level_id',
        'class_id', 
        'student_visit_id',
        'admission_test_id',
        'student_code',
        'first_name',
        'last_name', 
        'gender',
        'birth_date',
        'birth_place',
        'city',
        'repeat_count',
        'previous_school',
        'previous_level',
        'massar_code',
        'national_id',
        'passport_number',
        'nationality',
        'father_first_name',
        'father_last_name',
        'father_phone',
        'father_email',
        'father_profession',
        'father_national_id',
        'mother_first_name',
        'mother_last_name',
        'mother_phone',
        'mother_email',
        'mother_profession',
        'mother_national_id',
        'home_address',
        'postal_code',
        'neighborhood',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'blood_type',
        'medical_conditions',
        'medications',
        'has_special_needs',
        'special_needs_details',
        'dietary_restrictions',
        'transport_method',
        'bus_route',
        'meal_plan',
        'enrollment_date',
        'admission_score',
        'status',
        'observations',
        'student_photo',
        'documents',
        'is_active'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
        'admission_score' => 'decimal:2',
        'has_special_needs' => 'boolean',
        'is_active' => 'boolean',
        'repeat_count' => 'integer',
        'documents' => 'array'
    ];

    protected $appends = [
        'age',
        'status_label',
        'status_color'
    ];

    // Relationships
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function studentVisit()
    {
        return $this->belongsTo(StudentVisit::class);
    }


    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
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
            'active' => 'Actif',
            'suspended' => 'Suspendu',
            'graduated' => 'Diplômé',
            'transferred' => 'Transféré',
            'dropped_out' => 'Abandonné'
        ];

        return $statusLabels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $statusColors = [
            'active' => 'success',
            'suspended' => 'warning',
            'graduated' => 'info',
            'transferred' => 'primary',
            'dropped_out' => 'danger'
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

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('enrollment_date', 'desc');
    }

    // Generate unique student code
    public static function generateStudentCode($academicYear = null)
    {
        $academicYear = $academicYear ?: AcademicYear::current();
        if (!$academicYear) {
            return 'STU' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $yearCode = substr($academicYear->name, 2, 2) . substr($academicYear->name, -2);
        $count = self::where('academic_year_id', $academicYear->id)->count() + 1;
        
        return $yearCode . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Create student from accepted visit
    public static function createFromVisit(StudentVisit $visit)
    {
        $studentCode = self::generateStudentCode($visit->academicYear);

        return self::create([
            'academic_year_id' => $visit->academic_year_id,
            'school_id' => $visit->requestedSchool->id ?? null,
            'level_id' => $visit->requestedLevel->id ?? null,
            'student_visit_id' => $visit->id,
            'student_code' => $studentCode,
            'first_name' => $visit->student_first_name,
            'last_name' => $visit->student_last_name,
            'gender' => strtolower($visit->student_gender) === 'masculin' ? 'male' : 'female',
            'birth_date' => $visit->birth_date,
            'birth_place' => $visit->birth_place,
            'city' => $visit->city,
            'repeat_count' => $visit->repeat_count,
            'previous_school' => $visit->current_school,
            'previous_level' => $visit->current_level,
            'father_first_name' => $visit->father_first_name,
            'father_last_name' => $visit->father_last_name,
            'father_phone' => $visit->father_phone,
            'father_email' => $visit->father_email,
            'father_profession' => $visit->father_profession,
            'mother_first_name' => $visit->mother_first_name,
            'mother_last_name' => $visit->mother_last_name,
            'mother_phone' => $visit->mother_phone,
            'mother_email' => $visit->mother_email,
            'mother_profession' => $visit->mother_profession,
            'home_address' => $visit->city . ', Maroc',
            'observations' => $visit->observations,
            'enrollment_date' => now(),
            'status' => 'active',
            'nationality' => 'Moroccan'
        ]);
    }

    // Boot method for automatic academic year assignment
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (!$student->academic_year_id) {
                $currentYear = AcademicYear::current();
                if ($currentYear) {
                    $student->academic_year_id = $currentYear->id;
                }
            }

            if (!$student->student_code) {
                $student->student_code = self::generateStudentCode();
            }
        });
    }
}
