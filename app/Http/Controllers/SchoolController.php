<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function getLevels($schoolId)
    {
        try {
            $school = School::findOrFail($schoolId);
            
            // Get current academic year
            $currentYear = AcademicYear::where('is_current', true)->first();
            
            if (!$currentYear) {
                return response()->json(['error' => 'No current academic year set'], 400);
            }
            
            // Get levels for the school that belong to the current academic year
            $levels = $school->levels()
                ->where('academic_year_id', $currentYear->id)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
            
            return response()->json($levels);
        } catch (\Exception $e) {
            return response()->json(['error' => 'School not found'], 404);
        }
    }
}