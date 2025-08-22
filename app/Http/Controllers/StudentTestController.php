<?php

namespace App\Http\Controllers;

use App\Models\StudentTest;
use App\Models\StudentTestResult;
use App\Models\Test;
use App\Models\StudentVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudentTestController extends Controller
{
    public function assignStudents(Request $request, $testId)
    {
        $validatedData = $request->validate([
            'student_visit_ids' => 'required|array|min:1',
            'student_visit_ids.*' => 'exists:student_visits,id',
            'assigned_at' => 'nullable|date',
            'test_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $test = Test::findOrFail($testId);
            $assignedCount = 0;

            DB::transaction(function () use ($test, $validatedData, &$assignedCount) {
                foreach ($validatedData['student_visit_ids'] as $studentVisitId) {
                    // Check if already assigned
                    $exists = StudentTest::where('test_id', $test->id)
                        ->where('student_visit_id', $studentVisitId)
                        ->exists();

                    if (!$exists) {
                        StudentTest::create([
                            'test_id' => $test->id,
                            'student_visit_id' => $studentVisitId,
                            'assigned_at' => $validatedData['assigned_at'] ?? now(),
                            'notes' => $validatedData['notes'],
                            'status' => 'assigned'
                        ]);
                        
                        // Update student visit status to test_scheduled and set test_date
                        StudentVisit::where('id', $studentVisitId)
                            ->update([
                                'status' => 'test_scheduled',
                                'test_date' => $validatedData['test_date']
                            ]);
                        
                        $assignedCount++;
                    }
                }
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'assigned_count' => $assignedCount,
                    'test_id' => $test->id
                ],
                'message' => "$assignedCount student(s) assigned successfully"
            ]);

        } catch (\Exception $e) {
            Log::error('Error assigning students to test: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error assigning students: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAssignedStudents($testId)
    {
        try {
            $assignedStudents = StudentTest::with(['studentVisit', 'test'])
                ->where('test_id', $testId)
                ->orderBy('assigned_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $assignedStudents,
                'message' => 'Assigned students retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching assigned students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching assigned students'
            ], 500);
        }
    }

    public function unassignStudent($studentTestId)
    {
        try {
            $studentTest = StudentTest::with(['studentVisit'])->findOrFail($studentTestId);
            
            // Check if test has been started or completed
            if (in_array($studentTest->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot unassign student who has started or completed the test'
                ], 400);
            }

            // Update student visit status back to pending and clear test_date when unassigned
            $studentTest->studentVisit->update([
                'status' => 'pending',
                'test_date' => null
            ]);
            
            $studentTest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student unassigned successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error unassigning student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error unassigning student'
            ], 500);
        }
    }

    public function getTestResults(Request $request)
    {
        try {
            $query = StudentTest::with(['studentVisit', 'test.subjects', 'results.subject']);

            // Apply filters
            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }
            
            if ($request->filled('test_id')) {
                $query->where('test_id', $request->test_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('admission_decision')) {
                $query->where('admission_decision', $request->admission_decision);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('studentVisit', function ($q) use ($search) {
                    $q->where('student_first_name', 'LIKE', "%{$search}%")
                        ->orWhere('student_last_name', 'LIKE', "%{$search}%");
                });
            }

            // Filter by academic year through test relationship
            if ($request->filled('academic_year_id')) {
                $query->whereHas('test', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $request->get('per_page', 15);
            $results = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Test results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching test results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching test results'
            ], 500);
        }
    }

    public function updateTestResults(Request $request, $studentTestId)
    {
        $validatedData = $request->validate([
            'status' => 'required|in:assigned,in_progress,completed,absent',
            'test_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'results' => 'array',
            'results.*.subject_id' => 'required|exists:subjects,id',
            'results.*.score' => 'required|numeric|min:0',
            'results.*.max_score' => 'required|numeric|min:0',
            'results.*.remarks' => 'nullable|string'
        ]);

        try {
            $studentTest = StudentTest::with(['test.subjects'])->findOrFail($studentTestId);

            DB::transaction(function () use ($studentTest, $validatedData) {
                // Update student test record
                $updateData = [
                    'status' => $validatedData['status'],
                    'notes' => $validatedData['notes'] ?? null,
                ];

                // Update test_date in student_visits table if provided
                if (isset($validatedData['test_date'])) {
                    $studentTest->studentVisit->update([
                        'test_date' => $validatedData['test_date']
                    ]);
                }

                // Calculate total score if results provided
                if (isset($validatedData['results']) && !empty($validatedData['results'])) {
                    $totalScore = collect($validatedData['results'])->sum('score');
                    $totalMaxScore = collect($validatedData['results'])->sum('max_score');
                    
                    $updateData['total_score'] = $totalScore;
                    $updateData['percentage'] = $totalMaxScore > 0 ? round(($totalScore / $totalMaxScore) * 100, 2) : 0;
                    $updateData['passed'] = $totalScore >= $studentTest->test->passing_marks;
                }

                $studentTest->update($updateData);

                // Update student visit status to "tested" if test is completed
                if ($validatedData['status'] === 'completed') {
                    $studentTest->studentVisit->update(['status' => 'tested']);
                }

                // Update individual subject results
                if (isset($validatedData['results'])) {
                    // Delete existing results
                    $studentTest->results()->delete();

                    // Create new results
                    foreach ($validatedData['results'] as $result) {
                        StudentTestResult::create([
                            'student_test_id' => $studentTest->id,
                            'subject_id' => $result['subject_id'],
                            'score' => $result['score'],
                            'max_score' => $result['max_score'],
                            'remarks' => $result['remarks'] ?? null
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'data' => $studentTest->fresh(['studentVisit', 'test', 'results.subject']),
                'message' => 'Test results updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating test results: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating test results: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateAdmissionDecision(Request $request, $studentTestId)
    {
        $validatedData = $request->validate([
            'admission_decision' => 'required|in:pending,accepted,rejected',
            'notes' => 'nullable|string'
        ]);

        try {
            $studentTest = StudentTest::with(['studentVisit'])->findOrFail($studentTestId);

            $studentTest->update([
                'admission_decision' => $validatedData['admission_decision'],
                'notes' => $validatedData['notes'] ?? $studentTest->notes
            ]);

            // Update the student visit status if accepted
            if ($validatedData['admission_decision'] === 'accepted') {
                $studentTest->studentVisit->update(['status' => 'accepted']);
            } elseif ($validatedData['admission_decision'] === 'rejected') {
                $studentTest->studentVisit->update(['status' => 'rejected']);
            }

            return response()->json([
                'success' => true,
                'data' => $studentTest->fresh(['studentVisit', 'test']),
                'message' => 'Admission decision updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating admission decision: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating admission decision'
            ], 500);
        }
    }

    public function getDashboardStats(Request $request)
    {
        try {
            // Base query
            $baseQuery = StudentTest::query();
            
            // Filter by academic year through test relationship if provided
            if ($request->filled('academic_year_id')) {
                $baseQuery->whereHas('test', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }
            
            $stats = [
                'total_assignments' => (clone $baseQuery)->count(),
                'completed_tests' => (clone $baseQuery)->where('status', 'completed')->count(),
                'pending_tests' => (clone $baseQuery)->where('status', 'assigned')->count(),
                'passed_students' => (clone $baseQuery)->where('passed', true)->count(),
                'accepted_students' => (clone $baseQuery)->where('admission_decision', 'accepted')->count(),
                'rejected_students' => (clone $baseQuery)->where('admission_decision', 'rejected')->count(),
                'pending_decisions' => (clone $baseQuery)->where('admission_decision', 'pending')
                    ->where('status', 'completed')->count()
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
