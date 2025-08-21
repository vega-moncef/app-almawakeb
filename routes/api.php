<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentVisitController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\StudentTestController;
use App\Http\Controllers\LevelController;

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

Route::get('schools', function() {
    return response()->json([
        'success' => true,
        'data' => \App\Models\School::where('is_active', true)->orderBy('name')->get()
    ]);
});