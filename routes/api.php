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

// Subjects routes
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

Route::get('schools', function() {
    return response()->json([
        'success' => true,
        'data' => \App\Models\School::where('is_active', true)->orderBy('name')->get()
    ]);
});