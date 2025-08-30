<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentVisitController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\StudentTestController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\ClassSubjectController;
use App\Http\Controllers\TimetableController;

// Academic Year routes
Route::get('academic-years', [AcademicYearController::class, 'index']);
Route::get('academic-years/current', [AcademicYearController::class, 'getCurrent']);
Route::patch('academic-years/{year}/set-current', [AcademicYearController::class, 'setCurrent']);

// Student Visits - Routes spécifiques AVANT les routes resource
Route::get('student-visits/form-data', [StudentVisitController::class, 'getFormData']);
Route::patch('student-visits/{visit}/status', [StudentVisitController::class, 'updateStatus']);
Route::get('schools/{school}/levels', [StudentVisitController::class, 'getSchoolLevels']);
Route::get('admissions/dashboard-stats', [StudentVisitController::class, 'getDashboardStats']);

// Student Visits - Route resource APRÈS les routes spécifiques
Route::apiResource('student-visits', StudentVisitController::class);

// Tests - Routes spécifiques AVANT les routes resource
Route::get('tests/form-data', [TestController::class, 'getFormData']);
Route::get('tests/stats', [TestController::class, 'getDashboardStats']);
Route::post('tests/{test}/assign-students', [StudentTestController::class, 'assignStudents']);
Route::get('tests/{test}/assigned-students', [StudentTestController::class, 'getAssignedStudents']);

// Tests - Route resource APRÈS les routes spécifiques
Route::apiResource('tests', TestController::class);

// Subjects - Routes spécifiques AVANT les routes resource
Route::get('subjects/available', [ClassSubjectController::class, 'getAvailableSubjects']);
Route::get('subjects/{subject}/classes', [ClassSubjectController::class, 'getClassesForSubject']);

// Subjects - Route resource APRÈS les routes spécifiques
Route::apiResource('subjects', SubjectController::class);

// Student Tests routes
Route::get('student-tests', [StudentTestController::class, 'getTestResults']);
Route::put('student-tests/{studentTest}/results', [StudentTestController::class, 'updateTestResults']);
Route::patch('student-tests/{studentTest}/admission-decision', [StudentTestController::class, 'updateAdmissionDecision']);
Route::delete('student-tests/{studentTest}', [StudentTestController::class, 'unassignStudent']);
Route::get('student-tests/stats', [StudentTestController::class, 'getDashboardStats']);

// Additional utility routes
// Levels routes
Route::get('levels', [LevelController::class, 'index']);

// Classes routes
Route::get('classes', [ClassController::class, 'index']);
Route::post('classes', [ClassController::class, 'store']);
Route::put('classes/{class}', [ClassController::class, 'update']);
Route::delete('classes/{class}', [ClassController::class, 'destroy']);
Route::get('levels-by-school', [ClassController::class, 'getLevelsBySchool']);

// Students - Routes spécifiques AVANT les routes resource
Route::get('students/accepted-visits', [StudentController::class, 'getAcceptedVisits']);
Route::get('students/classes', [StudentController::class, 'getClasses']);
Route::get('students/stats', [StudentController::class, 'stats']);
Route::post('students/create-from-visit', [StudentController::class, 'createFromVisit']);

// Students - Route resource APRÈS les routes spécifiques
Route::apiResource('students', StudentController::class);

// Class Assignment routes
Route::get('class-assignments/levels', [App\Http\Controllers\ClassAssignmentController::class, 'getLevelsWithStudents']);
Route::get('class-assignments/level/{levelId}/students', [App\Http\Controllers\ClassAssignmentController::class, 'getStudentsByLevel']);
Route::post('class-assignments/assign', [App\Http\Controllers\ClassAssignmentController::class, 'assignStudentsToClass']);
Route::delete('class-assignments/student/{studentId}', [App\Http\Controllers\ClassAssignmentController::class, 'removeStudentFromClass']);
Route::put('class-assignments/move', [App\Http\Controllers\ClassAssignmentController::class, 'moveStudentToClass']);
Route::get('class-assignments/stats', [App\Http\Controllers\ClassAssignmentController::class, 'getAssignmentStats']);

// Teachers - Routes spécifiques AVANT les routes resource
Route::get('teachers/create', [TeacherController::class, 'create']);
Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit']);
Route::get('teachers/available', [TeacherController::class, 'getAvailableTeachers']);

// Teachers - Route resource APRÈS les routes spécifiques
Route::apiResource('teachers', TeacherController::class)->except(['create', 'edit']);

// Schools routes
Route::get('schools', function() {
    return response()->json([
        'success' => true,
        'data' => \App\Models\School::where('is_active', true)->orderBy('name')->get()
    ]);
});

// Class-Subject Management routes
Route::get('class-subjects', [ClassSubjectController::class, 'index']);
Route::get('class-subjects/{class}', [ClassSubjectController::class, 'show']);
Route::get('class-subjects/{class}/edit', [ClassSubjectController::class, 'edit']);
Route::put('class-subjects/{class}/subjects', [ClassSubjectController::class, 'updateSubjects']);
Route::post('class-subjects/assign', [ClassSubjectController::class, 'assignSubject']);
Route::delete('class-subjects/remove', [ClassSubjectController::class, 'removeSubject']);
Route::put('class-subjects/update-details', [ClassSubjectController::class, 'updateSubjectDetails']);
Route::post('class-subjects/bulk-assign', [ClassSubjectController::class, 'bulkAssignSubjects']);

// Timetable Management routes
Route::get('timetables/create', [TimetableController::class, 'create']);
Route::get('timetables/{timetable}/edit', [TimetableController::class, 'edit']);
Route::get('timetables/class/{class}/schedule', [TimetableController::class, 'getClassSchedule']);
Route::get('timetables/teacher/{teacher}/schedule', [TimetableController::class, 'getTeacherSchedule']);
Route::get('timetables/available-teachers', [TimetableController::class, 'getAvailableTeachers']);
Route::get('timetables/class/{class}/subjects', [TimetableController::class, 'getClassSubjects']);
Route::post('timetables/check-conflicts', [TimetableController::class, 'checkConflicts']);
Route::apiResource('timetables', TimetableController::class)->except(['create', 'edit']);

// Time Slot Management routes
Route::get('time-slots/break-types', [App\Http\Controllers\TimeSlotController::class, 'getBreakTypes']);
Route::post('time-slots/reorder', [App\Http\Controllers\TimeSlotController::class, 'reorder']);
Route::post('time-slots/{timeSlot}/toggle', [App\Http\Controllers\TimeSlotController::class, 'toggle']);

// Scoped time slot routes for better organization
Route::get('schools/{school}/time-slots', [App\Http\Controllers\TimeSlotController::class, 'index'])->name('school.timeslots');
Route::get('levels/{level}/time-slots', [App\Http\Controllers\TimeSlotController::class, 'index'])->name('level.timeslots');
Route::get('classes/{class}/time-slots', [App\Http\Controllers\TimeSlotController::class, 'index'])->name('class.timeslots');

Route::apiResource('time-slots', App\Http\Controllers\TimeSlotController::class);