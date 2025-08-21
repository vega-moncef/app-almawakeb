<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Subject;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Test::with(['academicYear', 'level', 'subjects']);

            // Apply filters
            if ($request->filled('level_id')) {
                $query->where('level_id', $request->level_id);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            } else {
                // Default to current academic year
                $query->forCurrentYear();
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $tests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tests,
                'message' => 'Tests retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tests: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tests: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:ORAL,ECRIT',
            'test_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'duration_minutes' => 'required|integer|min:1',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'required|numeric|min:0|lte:total_marks',
            'instructions' => 'nullable|string',
            'subjects' => 'required|array|min:1',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.marks' => 'required|numeric|min:0'
        ]);

        try {
            // Get current academic year
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            if (!$currentAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 400);
            }

            $validatedData['academic_year_id'] = $currentAcademicYear->id;

            // Handle file upload
            if ($request->hasFile('test_file')) {
                $file = $request->file('test_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('tests', $filename, 'public');
                $validatedData['test_file'] = $path;
            }

            // Create test
            $test = Test::create($validatedData);

            // Attach subjects with marks
            foreach ($validatedData['subjects'] as $index => $subjectData) {
                $test->subjects()->attach($subjectData['id'], [
                    'marks' => $subjectData['marks'],
                    'order' => $index + 1
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $test->load(['academicYear', 'level', 'subjects']),
                'message' => 'Test created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating test: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $test = Test::with(['academicYear', 'level', 'subjects', 'studentTests.studentVisit'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $test,
                'message' => 'Test retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:ORAL,ECRIT',
            'test_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'duration_minutes' => 'required|integer|min:1',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'required|numeric|min:0|lte:total_marks',
            'instructions' => 'nullable|string',
            'is_active' => 'boolean',
            'subjects' => 'required|array|min:1',
            'subjects.*.id' => 'required|exists:subjects,id',
            'subjects.*.marks' => 'required|numeric|min:0'
        ]);

        try {
            $test = Test::findOrFail($id);

            // Handle file upload
            if ($request->hasFile('test_file')) {
                // Delete old file if exists
                if ($test->test_file) {
                    Storage::disk('public')->delete($test->test_file);
                }
                
                $file = $request->file('test_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('tests', $filename, 'public');
                $validatedData['test_file'] = $path;
            }

            $test->update($validatedData);

            // Update subjects
            $test->subjects()->detach();
            foreach ($validatedData['subjects'] as $index => $subjectData) {
                $test->subjects()->attach($subjectData['id'], [
                    'marks' => $subjectData['marks'],
                    'order' => $index + 1
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $test->load(['academicYear', 'level', 'subjects']),
                'message' => 'Test updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating test: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $test = Test::findOrFail($id);
            
            // Delete test file if exists
            if ($test->test_file) {
                Storage::disk('public')->delete($test->test_file);
            }
            
            $test->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting test'
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

            $levels = Level::with('school')
                ->whereHas('school', function($query) {
                    $query->where('is_active', true);
                })
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('is_active', true)
                ->get()
                ->sortBy(function($level) {
                    // Create a sorting key based on school name and level order
                    $schoolPriority = 0;
                    $schoolName = strtolower($level->school->name ?? '');
                    
                    // Define school priority
                    if (str_contains($schoolName, 'primaire') || str_contains($schoolName, 'primary')) {
                        $schoolPriority = 1;
                    } elseif (str_contains($schoolName, 'collège') || str_contains($schoolName, 'college')) {
                        $schoolPriority = 2;
                    } elseif (str_contains($schoolName, 'lycée') || str_contains($schoolName, 'lycee') || str_contains($schoolName, 'secondaire')) {
                        $schoolPriority = 3;
                    }
                    
                    // Return composite sort key: school priority + level order
                    return ($schoolPriority * 1000) + ($level->order ?? 999);
                })
                ->values();

            $subjects = Subject::active()->ordered()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'levels' => $levels,
                    'subjects' => $subjects,
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
                'total_tests' => Test::where('academic_year_id', $currentYear->id)->count(),
                'oral_tests' => Test::where('academic_year_id', $currentYear->id)
                    ->where('type', 'ORAL')->count(),
                'written_tests' => Test::where('academic_year_id', $currentYear->id)
                    ->where('type', 'ECRIT')->count(),
                'active_tests' => Test::where('academic_year_id', $currentYear->id)
                    ->where('is_active', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Test stats retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching test stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching test stats'
            ], 500);
        }
    }
}
