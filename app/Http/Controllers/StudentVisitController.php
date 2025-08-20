<?php

namespace App\Http\Controllers;

use App\Models\StudentVisit;
use App\Models\School;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentVisitController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = StudentVisit::with(['requestedSchool', 'requestedLevel']);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('school_id')) {
                $query->where('requested_school_id', $request->school_id);
            }

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('student_first_name', 'LIKE', "%{$search}%")
                        ->orWhere('student_last_name', 'LIKE', "%{$search}%")
                        ->orWhere('father_first_name', 'LIKE', "%{$search}%")
                        ->orWhere('father_last_name', 'LIKE', "%{$search}%")
                        ->orWhere('mother_first_name', 'LIKE', "%{$search}%")
                        ->orWhere('mother_last_name', 'LIKE', "%{$search}%");
                });
            }

            // Order by most recent visits
            $query->orderBy('visit_date', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $visits = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $visits,
                'message' => 'Student visits retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching student visits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching student visits: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            // Student information
            'student_first_name' => 'required|string|max:255',
            'student_last_name' => 'required|string|max:255',
            'student_gender' => 'required|in:MASCULIN,FEMININ',
            'birth_date' => 'required|date',
            'birth_place' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'current_school' => 'nullable|string|max:255',
            'current_level' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:0',

            // Father information
            'father_first_name' => 'required|string|max:255',
            'father_last_name' => 'required|string|max:255',
            'father_phone' => 'required|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'father_profession' => 'nullable|string|max:255',

            // Mother information
            'mother_first_name' => 'required|string|max:255',
            'mother_last_name' => 'required|string|max:255',
            'mother_phone' => 'required|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'mother_profession' => 'nullable|string|max:255',

            // Request information
            'requested_school_id' => 'required|exists:schools,id',
            'requested_level_id' => 'nullable|exists:levels,id',
            'observations' => 'nullable|string',
            'visit_date' => 'required|date',
            'test_date' => 'nullable|date'
        ]);

        try {
            // Get current academic year
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found. Please set up an academic year first.'
                ], 400);
            }

            $validatedData['academic_year_id'] = $currentAcademicYear->id;
            $validatedData['status'] = 'pending';

            $studentVisit = StudentVisit::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $studentVisit->load(['requestedSchool', 'requestedLevel']),
                'message' => 'Student visit created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating student visit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating student visit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $visit = StudentVisit::with(['requestedSchool', 'requestedLevel', 'academicYear'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $visit,
                'message' => 'Student visit retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching student visit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Student visit not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            // Student information
            'student_first_name' => 'required|string|max:255',
            'student_last_name' => 'required|string|max:255',
            'student_gender' => 'required|in:MASCULIN,FEMININ',
            'birth_date' => 'required|date',
            'birth_place' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'current_school' => 'nullable|string|max:255',
            'current_level' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:0',

            // Father information
            'father_first_name' => 'required|string|max:255',
            'father_last_name' => 'required|string|max:255',
            'father_phone' => 'required|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'father_profession' => 'nullable|string|max:255',

            // Mother information
            'mother_first_name' => 'required|string|max:255',
            'mother_last_name' => 'required|string|max:255',
            'mother_phone' => 'required|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'mother_profession' => 'nullable|string|max:255',

            // Request information
            'requested_school_id' => 'required|exists:schools,id',
            'requested_level_id' => 'nullable|exists:levels,id',
            'observations' => 'nullable|string',
            'visit_date' => 'required|date',
            'test_date' => 'nullable|date',
            'status' => 'nullable|in:pending,test_scheduled,tested,accepted,rejected'
        ]);

        try {
            $visit = StudentVisit::findOrFail($id);
            $visit->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $visit->load(['requestedSchool', 'requestedLevel']),
                'message' => 'Student visit updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating student visit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating student visit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $visit = StudentVisit::findOrFail($id);
            $visit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student visit deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting student visit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting student visit'
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|in:pending,test_scheduled,tested,accepted,rejected'
        ]);

        try {
            $visit = StudentVisit::findOrFail($id);
            $visit->update(['status' => $validatedData['status']]);

            return response()->json([
                'success' => true,
                'data' => $visit,
                'message' => 'Status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating visit status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status'
            ], 500);
        }
    }

    public function getFormData()
    {
        try {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$currentAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 400);
            }

            // Get schools with levels for current academic year only
            $schools = School::with(['levels' => function ($query) use ($currentAcademicYear) {
                $query->where('academic_year_id', $currentAcademicYear->id)
                      ->where('is_active', true)
                      ->orderBy('order');
            }])
            ->where('is_active', true)
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'schools' => $schools,
                    'current_academic_year' => $currentAcademicYear
                ],
                'message' => 'Form data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching form data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching form data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSchoolLevels($schoolId)
    {
        try {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$currentAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 400);
            }

            $levels = Level::where('school_id', $schoolId)
                           ->where('academic_year_id', $currentAcademicYear->id)
                           ->where('is_active', true)
                           ->orderBy('order')
                           ->get();

            return response()->json([
                'success' => true,
                'data' => $levels,
                'message' => 'School levels retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching school levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching school levels'
            ], 500);
        }
    }

    public function getDashboardStats()
    {
        try {
            $currentYear = AcademicYear::where('is_current', true)->first();
            
            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 400);
            }

            $stats = [
                'total_visits' => StudentVisit::where('academic_year_id', $currentYear->id)->count(),
                'pending_visits' => StudentVisit::where('academic_year_id', $currentYear->id)
                    ->where('status', 'pending')->count(),
                'scheduled_tests' => StudentVisit::where('academic_year_id', $currentYear->id)
                    ->where('status', 'test_scheduled')->count(),
                'accepted_students' => StudentVisit::where('academic_year_id', $currentYear->id)
                    ->where('status', 'accepted')->count(),
                'recent_visits' => StudentVisit::with(['requestedSchool'])
                    ->where('academic_year_id', $currentYear->id)
                    ->orderBy('visit_date', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard stats retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard stats'
            ], 500);
        }
    }
}