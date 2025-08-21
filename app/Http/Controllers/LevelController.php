<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LevelController extends Controller
{
    public function index(Request $request)
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
                
            return response()->json([
                'success' => true,
                'data' => $levels,
                'message' => 'Levels retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching levels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching levels'
            ], 500);
        }
    }
}