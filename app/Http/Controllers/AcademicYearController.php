<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AcademicYearController extends Controller
{
    public function index()
    {
        try {
            $academicYears = AcademicYear::where('is_active', true)
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $academicYears,
                'message' => 'Academic years retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching academic years: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching academic years'
            ], 500);
        }
    }

    public function getCurrent()
    {
        try {
            $currentYear = AcademicYear::where('is_current', true)->first();

            if (!$currentYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $currentYear,
                'message' => 'Current academic year retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching current academic year: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching current academic year'
            ], 500);
        }
    }

    public function setCurrent(Request $request, $id)
    {
        try {
            // Validate that the academic year exists
            $academicYear = AcademicYear::findOrFail($id);

            // Remove is_current from all academic years
            AcademicYear::where('is_current', true)->update(['is_current' => false]);

            // Set the selected year as current
            $academicYear->update(['is_current' => true]);

            return response()->json([
                'success' => true,
                'data' => $academicYear,
                'message' => 'Academic year set as current successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error setting current academic year: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error setting current academic year'
            ], 500);
        }
    }
}