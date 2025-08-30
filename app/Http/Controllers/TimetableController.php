<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $query = Timetable::with(['classRoom.level', 'teacher', 'subject', 'timeSlot', 'academicYear']);
        
        // Filter by academic year (default current)
        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $currentYear = AcademicYear::current();
            $academicYearId = $currentYear?->id;
        }
        
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        // Filter by class
        if ($request->has('class_id') && $request->get('class_id')) {
            $query->where('class_id', $request->get('class_id'));
        }
        
        // Filter by teacher
        if ($request->has('teacher_id') && $request->get('teacher_id')) {
            $query->where('teacher_id', $request->get('teacher_id'));
        }
        
        // Filter by day
        if ($request->has('day_of_week') && $request->get('day_of_week')) {
            $query->where('day_of_week', $request->get('day_of_week'));
        }
        
        $timetables = $query->active()
                           ->orderBy('day_of_week')
                           ->orderBy('time_slot_id')
                           ->paginate(50);
        
        return response()->json([
            'timetables' => $timetables,
            'days' => Timetable::getDays(),
            'current_academic_year_id' => $academicYearId
        ]);
    }

    public function create()
    {
        $currentYear = AcademicYear::current();
        
        $classes = ClassRoom::with(['level'])
            ->forCurrentYear()
            ->active()
            ->orderBy('name')
            ->get();
            
        $teachers = Teacher::with(['subjects'])
            ->active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
            
        $subjects = Subject::active()
            ->ordered()
            ->get();
            
        // Get all active time slots (including breaks for display purposes)
        $timeSlots = TimeSlot::active()
            ->ordered()
            ->get();
            
        $levels = \App\Models\Level::active()
            ->forCurrentYear()
            ->get();
            
        // Custom ordering for levels
        $levelOrder = [
            'PETITE SECTION' => 1, 'PS' => 1, 'PETIT SECTION' => 1,
            'MOYENNE SECTION' => 2, 'MS' => 2, 'MOYEN SECTION' => 2,
            'GRANDE SECTION' => 3, 'GS' => 3, 'GRAND SECTION' => 3,
            'CP' => 4,
            'CE1' => 5,
            'CE2' => 6,
            'CM1' => 7,
            'CM2' => 8,
            '6EME' => 9, '6ÈME' => 9, 'SIXIEME' => 9, 'SIXIÈME' => 9, 'CM6' => 9,
            '1APIC' => 10,
            '2APIC' => 11,
            '3APIC' => 12,
            'TC' => 13, 'TRONC COMMUN' => 13,
            '1BAC' => 14,
            '2BAC' => 15,
            '3BAC' => 16
        ];
        
        $levels = $levels->sortBy(function ($level) use ($levelOrder) {
            $levelName = strtoupper(trim($level->name));
            return $levelOrder[$levelName] ?? 999;
        })->values();
            
        return response()->json([
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'timeSlots' => $timeSlots,
            'levels' => $levels,
            'days' => Timetable::getDays(),
            'academicYear' => $currentYear
        ]);
    }

    public function store(Request $request)
    {
        $currentYear = AcademicYear::current();
        
        if (!$currentYear) {
            return response()->json(['error' => 'No current academic year set'], 400);
        }

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|integer|between:1,5',
            'is_active' => 'boolean'
        ]);

        // Check if teacher teaches this subject
        $teacher = Teacher::find($validated['teacher_id']);
        if (!$teacher->subjects->contains($validated['subject_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher does not teach this subject'
            ], 422);
        }

        // Check if subject is assigned to this class
        $class = ClassRoom::find($validated['class_id']);
        if (!$class->subjects->contains($validated['subject_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is not assigned to this class'
            ], 422);
        }

        // Check for conflicts
        if (Timetable::hasConflict(
            $validated['teacher_id'],
            $validated['class_id'],
            $validated['time_slot_id'],
            $validated['day_of_week'],
            $currentYear->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule conflict detected. Either the teacher or the class is already assigned at this time.'
            ], 422);
        }

        $validated['academic_year_id'] = $currentYear->id;
        
        $timetable = Timetable::create($validated);
        $timetable->load(['classRoom.level', 'teacher', 'subject', 'timeSlot']);

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry created successfully',
            'timetable' => $timetable
        ], 201);
    }

    public function show(Timetable $timetable)
    {
        $timetable->load(['classRoom.level', 'teacher', 'subject', 'timeSlot', 'academicYear']);
        
        return response()->json([
            'timetable' => $timetable
        ]);
    }

    public function edit(Timetable $timetable)
    {
        $timetable->load(['classRoom.level', 'teacher', 'subject', 'timeSlot']);
        
        $classes = ClassRoom::with(['level'])
            ->forYear($timetable->academic_year_id)
            ->active()
            ->orderBy('name')
            ->get();
            
        $teachers = Teacher::active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
            
        $subjects = Subject::active()
            ->ordered()
            ->get();
            
        $timeSlots = TimeSlot::active()
            ->regularClasses()
            ->ordered()
            ->get();
            
        return response()->json([
            'timetable' => $timetable,
            'classes' => $classes,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'timeSlots' => $timeSlots,
            'days' => Timetable::getDays()
        ]);
    }

    public function update(Request $request, Timetable $timetable)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|integer|between:1,5',
            'is_active' => 'boolean'
        ]);

        // Check if teacher teaches this subject
        $teacher = Teacher::find($validated['teacher_id']);
        if (!$teacher->subjects->contains($validated['subject_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher does not teach this subject'
            ], 422);
        }

        // Check if subject is assigned to this class
        $class = ClassRoom::find($validated['class_id']);
        if (!$class->subjects->contains($validated['subject_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is not assigned to this class'
            ], 422);
        }

        // Check for conflicts (excluding current timetable)
        if (Timetable::hasConflict(
            $validated['teacher_id'],
            $validated['class_id'],
            $validated['time_slot_id'],
            $validated['day_of_week'],
            $timetable->academic_year_id,
            $timetable->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule conflict detected. Either the teacher or the class is already assigned at this time.'
            ], 422);
        }

        $timetable->update($validated);
        $timetable->load(['classRoom.level', 'teacher', 'subject', 'timeSlot']);

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry updated successfully',
            'timetable' => $timetable
        ]);
    }

    public function destroy(Timetable $timetable)
    {
        $timetable->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timetable entry deleted successfully'
        ]);
    }

    // Get class schedule view
    public function getClassSchedule(Request $request, $classId)
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id'
        ]);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::current()?->id;
        
        if (!$academicYearId) {
            return response()->json(['error' => 'No academic year specified'], 400);
        }

        $class = ClassRoom::with(['level'])->findOrFail($classId);
        
        $timetables = Timetable::with(['teacher', 'subject', 'timeSlot'])
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->active()
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get();

        // Organize by day and time slot
        $schedule = [];
        foreach (Timetable::getDays() as $dayNum => $dayName) {
            $schedule[$dayNum] = [
                'day_name' => $dayName,
                'slots' => []
            ];
        }

        // Get time slots applicable to this class (including breaks and scoped ones)
        $timeSlots = $this->getTimeSlotsForClass($class);
        
        foreach ($timetables as $timetable) {
            $schedule[$timetable->day_of_week]['slots'][$timetable->time_slot_id] = $timetable;
        }

        return response()->json([
            'class' => $class,
            'schedule' => $schedule,
            'timeSlots' => $timeSlots,
            'days' => Timetable::getDays()
        ]);
    }

    // Get teacher schedule view
    public function getTeacherSchedule(Request $request, $teacherId)
    {
        $validated = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id'
        ]);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::current()?->id;
        
        if (!$academicYearId) {
            return response()->json(['error' => 'No academic year specified'], 400);
        }

        $teacher = Teacher::with(['subjects'])->findOrFail($teacherId);
        
        $timetables = Timetable::with(['classRoom.level', 'subject', 'timeSlot'])
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->active()
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get();

        // Organize by day and time slot
        $schedule = [];
        foreach (Timetable::getDays() as $dayNum => $dayName) {
            $schedule[$dayNum] = [
                'day_name' => $dayName,
                'slots' => []
            ];
        }

        // Get time slots applicable to this teacher (including breaks and scoped ones)
        $timeSlots = $this->getTimeSlotsForTeacher($teacher);
        
        foreach ($timetables as $timetable) {
            $schedule[$timetable->day_of_week]['slots'][$timetable->time_slot_id] = $timetable;
        }

        return response()->json([
            'teacher' => $teacher,
            'schedule' => $schedule,
            'timeSlots' => $timeSlots,
            'days' => Timetable::getDays()
        ]);
    }

    // Get available teachers for a specific time slot and subject
    public function getAvailableTeachers(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|integer|between:1,5',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'exclude_timetable_id' => 'nullable|exists:timetables,id'
        ]);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::current()?->id;
        
        if (!$academicYearId) {
            return response()->json(['error' => 'No academic year specified'], 400);
        }

        $conflictQuery = Timetable::where('academic_year_id', $academicYearId)
                                 ->where('time_slot_id', $validated['time_slot_id'])
                                 ->where('day_of_week', $validated['day_of_week'])
                                 ->active();

        if (isset($validated['exclude_timetable_id'])) {
            $conflictQuery->where('id', '!=', $validated['exclude_timetable_id']);
        }

        $busyTeacherIds = $conflictQuery->pluck('teacher_id');

        $availableTeachers = Teacher::active()
            ->whereHas('subjects', function($query) use ($validated) {
                $query->where('subject_id', $validated['subject_id']);
            })
            ->whereNotIn('id', $busyTeacherIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'teachers' => $availableTeachers
        ]);
    }

    // Get subjects available for a class (only subjects assigned to the class)
    public function getClassSubjects(Request $request, $classId)
    {
        $class = ClassRoom::with(['subjects' => function($query) {
            $query->where('class_subject.is_active', true)->ordered();
        }])->findOrFail($classId);

        return response()->json([
            'subjects' => $class->subjects
        ]);
    }

    // Get schedule conflicts for validation
    public function checkConflicts(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:classes,id',
            'time_slot_id' => 'required|exists:time_slots,id',
            'day_of_week' => 'required|integer|between:1,5',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'exclude_timetable_id' => 'nullable|exists:timetables,id'
        ]);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::current()?->id;
        
        if (!$academicYearId) {
            return response()->json(['error' => 'No academic year specified'], 400);
        }

        $hasConflict = Timetable::hasConflict(
            $validated['teacher_id'],
            $validated['class_id'],
            $validated['time_slot_id'],
            $validated['day_of_week'],
            $academicYearId,
            $validated['exclude_timetable_id'] ?? null
        );

        return response()->json([
            'has_conflict' => $hasConflict
        ]);
    }

    /**
     * Get time slots applicable to a specific class
     * This includes global, school-level, level-level, and class-specific slots
     */
    private function getTimeSlotsForClass($class)
    {
        return TimeSlot::active()
            ->where(function($query) use ($class) {
                // Global time slots (null scope)
                $query->whereNull('school_id')
                      ->whereNull('level_id')
                      ->whereNull('class_id');
                
                // OR School-specific time slots
                if ($class->level && $class->level->school_id) {
                    $query->orWhere(function($q) use ($class) {
                        $q->where('school_id', $class->level->school_id)
                          ->whereNull('level_id')
                          ->whereNull('class_id');
                    });
                }
                
                // OR Level-specific time slots
                if ($class->level_id) {
                    $query->orWhere(function($q) use ($class) {
                        $q->where('level_id', $class->level_id)
                          ->whereNull('school_id')
                          ->whereNull('class_id');
                    });
                }
                
                // OR Class-specific time slots
                $query->orWhere(function($q) use ($class) {
                    $q->where('class_id', $class->id)
                      ->whereNull('school_id')
                      ->whereNull('level_id');
                });
            })
            ->ordered()
            ->get();
    }

    /**
     * Get time slots applicable to a specific teacher
     * This includes global slots and any class-specific slots for classes they teach
     */
    private function getTimeSlotsForTeacher($teacher)
    {
        // For teachers, we'll get all time slots they might encounter based on their assigned classes
        $teacherClasses = ClassRoom::whereHas('timetables', function($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })->with('level')->get();

        $query = TimeSlot::active();
        
        // Start with global time slots
        $query->where(function($mainQuery) use ($teacherClasses) {
            // Global time slots
            $mainQuery->whereNull('school_id')
                      ->whereNull('level_id')  
                      ->whereNull('class_id');
            
            // Add scoped time slots for each class the teacher teaches
            foreach ($teacherClasses as $class) {
                // School-specific
                if ($class->level && $class->level->school_id) {
                    $mainQuery->orWhere(function($q) use ($class) {
                        $q->where('school_id', $class->level->school_id)
                          ->whereNull('level_id')
                          ->whereNull('class_id');
                    });
                }
                
                // Level-specific
                if ($class->level_id) {
                    $mainQuery->orWhere(function($q) use ($class) {
                        $q->where('level_id', $class->level_id)
                          ->whereNull('school_id')
                          ->whereNull('class_id');
                    });
                }
                
                // Class-specific
                $mainQuery->orWhere(function($q) use ($class) {
                    $q->where('class_id', $class->id)
                      ->whereNull('school_id')
                      ->whereNull('level_id');
                });
            }
        });
        
        return $query->ordered()->get();
    }
}