<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class ClassSubjectController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ClassRoom::with(['level', 'academicYear', 'subjects']);
            
            // Filter by academic year (default current)
            $academicYearId = $request->get('academic_year_id');
            if (!$academicYearId) {
                $currentYear = AcademicYear::current();
                $academicYearId = $currentYear?->id;
            }
            
            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            
            // Filter by level
            if ($request->has('level_id') && $request->get('level_id')) {
                $query->where('level_id', $request->get('level_id'));
            }
            
            $classes = $query->where('is_active', true)
                            ->orderBy('name')
                            ->paginate(15);
            
            return response()->json([
                'classes' => $classes,
                'current_academic_year_id' => $academicYearId
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ClassSubjectController@index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($classId)
    {
        $class = ClassRoom::with(['level', 'academicYear', 'subjects' => function($query) {
            $query->orderBy('name');
        }])->findOrFail($classId);
        
        return response()->json([
            'class' => $class
        ]);
    }

    public function edit($classId)
    {
        $class = ClassRoom::with(['level', 'academicYear', 'subjects'])->findOrFail($classId);
        
        $allSubjects = Subject::active()->ordered()->get();
        $assignedSubjectIds = $class->subjects->pluck('id')->toArray();
        
        return response()->json([
            'class' => $class,
            'subjects' => $allSubjects,
            'assignedSubjectIds' => $assignedSubjectIds
        ]);
    }

    public function updateSubjects(Request $request, $classId)
    {
        $class = ClassRoom::findOrFail($classId);
        
        $validated = $request->validate([
            'subjects' => 'array',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.hours_per_week' => 'nullable|integer|min:1|max:20',
            'subjects.*.is_active' => 'boolean'
        ]);

        $syncData = [];
        
        if (isset($validated['subjects'])) {
            foreach ($validated['subjects'] as $subject) {
                $syncData[$subject['id']] = [
                    'hours_per_week' => $subject['hours_per_week'] ?? null,
                    'is_active' => $subject['is_active'] ?? true
                ];
            }
        }
        
        $class->subjects()->sync($syncData);
        
        // Reload the class with updated subjects
        $class->load(['subjects' => function($query) {
            $query->orderBy('name');
        }]);
        
        return response()->json([
            'success' => true,
            'message' => 'Class subjects updated successfully',
            'class' => $class
        ]);
    }

    public function assignSubject(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'hours_per_week' => 'nullable|integer|min:1|max:20',
            'is_active' => 'boolean'
        ]);

        $class = ClassRoom::findOrFail($validated['class_id']);
        
        // Check if subject is already assigned
        if ($class->subjects()->where('subject_id', $validated['subject_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is already assigned to this class'
            ], 422);
        }
        
        $class->subjects()->attach($validated['subject_id'], [
            'hours_per_week' => $validated['hours_per_week'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject assigned to class successfully'
        ]);
    }

    public function removeSubject(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id'
        ]);

        $class = ClassRoom::findOrFail($validated['class_id']);
        
        // Check if there are active timetable entries for this class-subject combination
        $hasTimetableEntries = $class->timetables()
            ->where('subject_id', $validated['subject_id'])
            ->where('is_active', true)
            ->exists();
            
        if ($hasTimetableEntries) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove subject. There are active timetable entries for this class-subject combination.'
            ], 422);
        }
        
        $class->subjects()->detach($validated['subject_id']);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject removed from class successfully'
        ]);
    }

    public function updateSubjectDetails(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'hours_per_week' => 'nullable|integer|min:1|max:20',
            'is_active' => 'boolean'
        ]);

        $class = ClassRoom::findOrFail($validated['class_id']);
        
        $class->subjects()->updateExistingPivot($validated['subject_id'], [
            'hours_per_week' => $validated['hours_per_week'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Subject details updated successfully'
        ]);
    }

    // Get classes that teach a specific subject
    public function getClassesForSubject($subjectId)
    {
        $subject = Subject::with(['classes' => function($query) {
            $query->with(['level', 'academicYear'])
                  ->active()
                  ->orderBy('name');
        }])->findOrFail($subjectId);
        
        return response()->json([
            'subject' => $subject
        ]);
    }

    // Get all subjects available for assignment
    public function getAvailableSubjects(Request $request)
    {
        try {
            // Start with all active subjects with their teachers
            $query = Subject::with(['teachers:id,first_name,last_name'])
                          ->active()
                          ->ordered();
            
            // Filter subjects not assigned to a specific class
            if ($request->has('exclude_class_id') && $request->get('exclude_class_id')) {
                $classId = $request->get('exclude_class_id');
                
                // Get assigned subject IDs for this class using raw SQL to avoid relationship issues
                $assignedSubjectIds = \DB::table('class_subject')
                    ->where('class_id', $classId)
                    ->where('is_active', true)
                    ->pluck('subject_id')
                    ->toArray();
                
                if (!empty($assignedSubjectIds)) {
                    $query->whereNotIn('id', $assignedSubjectIds);
                }
            }
            
            $subjects = $query->get();
            
            // Transform subjects to create separate entries for each teacher
            $subjectsWithTeachers = collect();
            
            foreach ($subjects as $subject) {
                if ($subject->teachers->count() > 0) {
                    // Create separate entries for each teacher
                    foreach ($subject->teachers as $teacher) {
                        $subjectCopy = $subject->toArray();
                        $subjectCopy['teacher'] = $teacher;
                        $subjectCopy['display_name'] = $subject->name . ' (' . $teacher->first_name . ' ' . $teacher->last_name . ')';
                        $subjectCopy['unique_id'] = $subject->id . '_' . $teacher->id;
                        unset($subjectCopy['teachers']);
                        $subjectsWithTeachers->push($subjectCopy);
                    }
                } else {
                    // Subject without assigned teachers
                    $subjectCopy = $subject->toArray();
                    $subjectCopy['teacher'] = null;
                    $subjectCopy['display_name'] = $subject->name . ' (Aucun enseignant assigné)';
                    $subjectCopy['unique_id'] = $subject->id . '_no_teacher';
                    unset($subjectCopy['teachers']);
                    $subjectsWithTeachers->push($subjectCopy);
                }
            }
            
            return response()->json([
                'subjects' => $subjectsWithTeachers
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in ClassSubjectController@getAvailableSubjects: ' . $e->getMessage());
            return response()->json([
                'error' => 'Server error occurred',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    // Bulk assign subjects to multiple classes
    public function bulkAssignSubjects(Request $request)
    {
        $validated = $request->validate([
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'hours_per_week' => 'nullable|integer|min:1|max:20',
            'is_active' => 'boolean'
        ]);

        $assignmentData = [
            'hours_per_week' => $validated['hours_per_week'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ];

        $successCount = 0;
        $skippedCount = 0;
        
        foreach ($validated['class_ids'] as $classId) {
            $class = ClassRoom::find($classId);
            
            if ($class && !$class->subjects()->where('subject_id', $validated['subject_id'])->exists()) {
                $class->subjects()->attach($validated['subject_id'], $assignmentData);
                $successCount++;
            } else {
                $skippedCount++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => "Subject assigned to {$successCount} classes. {$skippedCount} classes were skipped (already assigned).",
            'assigned_count' => $successCount,
            'skipped_count' => $skippedCount
        ]);
    }
}