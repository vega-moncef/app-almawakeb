<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentVisitController;
use App\Http\Controllers\AcademicYearController;

// Academic Year routes
Route::get('academic-years', [AcademicYearController::class, 'index']);
Route::get('academic-years/current', [AcademicYearController::class, 'getCurrent']);
Route::patch('academic-years/{year}/set-current', [AcademicYearController::class, 'setCurrent']);

// Routes spécifiques AVANT les routes resource
Route::get('student-visits/form-data', [StudentVisitController::class, 'getFormData']);
Route::patch('student-visits/{visit}/status', [StudentVisitController::class, 'updateStatus']);
Route::get('schools/{school}/levels', [StudentVisitController::class, 'getSchoolLevels']);
Route::get('admissions/dashboard-stats', [StudentVisitController::class, 'getDashboardStats']);

// Route resource APRÈS les routes spécifiques
Route::apiResource('student-visits', StudentVisitController::class);