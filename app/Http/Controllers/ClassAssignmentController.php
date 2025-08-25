<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ClassAssignmentController extends Controller
{
    /**
     * Get all levels with students and classes for assignment interface
     */
    public function getLevelsWithStudents(Request $request): JsonResponse
    {
        $academicYearId = $request->get('academic_year_id');
        
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear?->id;
        }

        if (!$academicYearId) {
            return response()->json([
                'success' => false,
                'message' => 'No academic year found'
            ], 400);
        }

        try {
            $levels = Level::with([
                'school',
                'classes' => function($query) use ($academicYearId) {
                    $query->where('academic_year_id', $academicYearId)
                          ->where('is_active', true)
                          ->withCount('students');
                },
                'classes.students' => function($query) use ($academicYearId) {
                    $query->where('academic_year_id', $academicYearId)
                          ->where('is_active', true);
                }
            ])
            ->whereHas('school', function($query) {
                $query->where('is_active', true);
            })
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->get();

            // Get unassigned students for each level
            foreach ($levels as $level) {
                // Get students that belong specifically to this level
                $level->unassigned_students = Student::where('academic_year_id', $academicYearId)
                    ->where('is_active', true)
                    ->whereNull('class_id')
                    ->where('level_id', $level->id)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $levels,
                'academic_year_id' => $academicYearId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading levels data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students by level for assignment
     */
    public function getStudentsByLevel(Request $request, $levelId): JsonResponse
    {
        $academicYearId = $request->get('academic_year_id');
        
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear?->id;
        }

        try {
            $level = Level::with(['school', 'classes' => function($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId)->where('is_active', true);
            }])->findOrFail($levelId);

            // Get all students for this level (assigned and unassigned)
            $assignedStudents = Student::with('class')
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->whereHas('class', function($query) use ($levelId) {
                    $query->where('level_id', $levelId);
                })
                ->get();

            // Get unassigned students that belong specifically to this level
            $unassignedStudents = Student::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->whereNull('class_id')
                ->where('level_id', $levelId)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'level' => $level,
                    'assigned_students' => $assignedStudents,
                    'unassigned_students' => $unassignedStudents
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading students: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign multiple students to a class
     */
    public function assignStudentsToClass(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id'
        ]);

        try {
            DB::beginTransaction();

            $class = ClassRoom::findOrFail($request->class_id);
            $studentIds = $request->student_ids;

            // Check class capacity
            $currentCount = $class->students()->count();
            $newCount = count($studentIds);
            
            if (($currentCount + $newCount) > $class->capacity) {
                return response()->json([
                    'success' => false,
                    'message' => "La classe {$class->full_name} n'a pas assez de places. Capacité: {$class->capacity}, Actuellement: {$currentCount}, Tentative d'ajout: {$newCount}"
                ], 400);
            }

            // Assign students to the class
            Student::whereIn('id', $studentIds)->update(['class_id' => $class->id]);

            // Update class current students count
            $class->current_students = $class->students()->count();
            $class->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($studentIds) . ' élève(s) assigné(s) à la classe ' . $class->full_name,
                'data' => [
                    'class' => $class->load('students'),
                    'assigned_count' => count($studentIds)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove student from class
     */
    public function removeStudentFromClass(Request $request, $studentId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($studentId);
            $oldClass = $student->class;

            // Remove from class
            $student->class_id = null;
            $student->save();

            // Update old class count if it existed
            if ($oldClass) {
                $oldClass->current_students = $oldClass->students()->count();
                $oldClass->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "L'élève {$student->first_name} {$student->last_name} a été retiré de sa classe",
                'data' => [
                    'student' => $student,
                    'old_class' => $oldClass
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move student from one class to another
     */
    public function moveStudentToClass(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'new_class_id' => 'required|exists:classes,id'
        ]);

        try {
            DB::beginTransaction();

            $student = Student::findOrFail($request->student_id);
            $newClass = ClassRoom::findOrFail($request->new_class_id);
            $oldClass = $student->class;

            // Check new class capacity
            $currentCount = $newClass->students()->count();
            if ($currentCount >= $newClass->capacity) {
                return response()->json([
                    'success' => false,
                    'message' => "La classe {$newClass->full_name} est pleine. Capacité: {$newClass->capacity}"
                ], 400);
            }

            // Move student to new class
            $student->class_id = $newClass->id;
            $student->save();

            // Update class counts
            $newClass->current_students = $newClass->students()->count();
            $newClass->save();

            if ($oldClass) {
                $oldClass->current_students = $oldClass->students()->count();
                $oldClass->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "L'élève {$student->first_name} {$student->last_name} a été déplacé vers {$newClass->full_name}",
                'data' => [
                    'student' => $student->load('class'),
                    'old_class' => $oldClass,
                    'new_class' => $newClass
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déplacement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get class assignment statistics
     */
    public function getAssignmentStats(Request $request): JsonResponse
    {
        $academicYearId = $request->get('academic_year_id');
        
        if (!$academicYearId) {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            $academicYearId = $currentAcademicYear?->id;
        }

        try {
            $totalStudents = Student::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->count();

            $assignedStudents = Student::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->whereNotNull('class_id')
                ->count();

            $unassignedStudents = $totalStudents - $assignedStudents;

            $totalClasses = ClassRoom::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->count();

            $occupiedClasses = ClassRoom::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->whereHas('students')
                ->count();

            $totalCapacity = ClassRoom::where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->sum('capacity');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_students' => $totalStudents,
                    'assigned_students' => $assignedStudents,
                    'unassigned_students' => $unassignedStudents,
                    'assignment_percentage' => $totalStudents > 0 ? round(($assignedStudents / $totalStudents) * 100, 1) : 0,
                    'total_classes' => $totalClasses,
                    'occupied_classes' => $occupiedClasses,
                    'total_capacity' => $totalCapacity,
                    'capacity_utilization' => $totalCapacity > 0 ? round(($assignedStudents / $totalCapacity) * 100, 1) : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}