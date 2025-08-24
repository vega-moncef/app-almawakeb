<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Level;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClassController extends Controller
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

            $classes = ClassRoom::with(['level.school', 'academicYear'])
                ->whereHas('level.school', function($query) {
                    $query->where('is_active', true);
                })
                ->whereHas('level', function($query) {
                    $query->where('is_active', true);
                })
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('is_active', true)
                ->get()
                ->sortBy(function($class) {
                    $schoolPriority = 0;
                    $schoolName = strtolower($class->level->school->name ?? '');
                    
                    if (str_contains($schoolName, 'maternelle')) {
                        $schoolPriority = 0;
                    } elseif (str_contains($schoolName, 'primaire')) {
                        $schoolPriority = 1;
                    } elseif (str_contains($schoolName, 'collège')) {
                        $schoolPriority = 2;
                    } elseif (str_contains($schoolName, 'lycée')) {
                        $schoolPriority = 3;
                    }
                    
                    return ($schoolPriority * 100000) + ($class->level->order ?? 999) * 100 + ord(strtoupper($class->name));
                })
                ->values();
                
            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'Classes retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching classes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching classes'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'level_id' => 'required|exists:levels,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'name' => 'required|string|max:10',
                'capacity' => 'required|integer|min:1|max:50',
            ]);

            // Get the level to generate full_name
            $level = Level::find($request->level_id);
            $fullName = $level->name . ' ' . $request->name;

            // Check if class already exists for this level and academic year
            $existingClass = ClassRoom::where('level_id', $request->level_id)
                ->where('academic_year_id', $request->academic_year_id)
                ->where('name', $request->name)
                ->first();

            if ($existingClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une classe avec ce nom existe déjà pour ce niveau'
                ], 422);
            }

            $class = ClassRoom::create([
                'level_id' => $request->level_id,
                'academic_year_id' => $request->academic_year_id,
                'name' => $request->name,
                'full_name' => $fullName,
                'capacity' => $request->capacity,
                'current_students' => 0,
                'is_active' => true
            ]);

            $class->load(['level.school', 'academicYear']);

            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'Classe créée avec succès'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating class: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la classe'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $class = ClassRoom::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:10',
                'capacity' => 'sometimes|required|integer|min:1|max:50',
                'is_active' => 'sometimes|boolean',
            ]);

            // Check if name is being updated and if it already exists
            if ($request->has('name') && $request->name !== $class->name) {
                $existingClass = ClassRoom::where('level_id', $class->level_id)
                    ->where('academic_year_id', $class->academic_year_id)
                    ->where('name', $request->name)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingClass) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Une classe avec ce nom existe déjà pour ce niveau'
                    ], 422);
                }

                // Update full_name if name changed
                $level = $class->level;
                $class->full_name = $level->name . ' ' . $request->name;
            }

            $class->update($request->only(['name', 'capacity', 'is_active']));
            
            if ($request->has('name')) {
                $class->update(['full_name' => $class->full_name]);
            }

            $class->load(['level.school', 'academicYear']);

            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'Classe mise à jour avec succès'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating class: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la classe'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $class = ClassRoom::findOrFail($id);

            // Check if class has students
            if ($class->current_students > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une classe qui contient des étudiants'
                ], 422);
            }

            $class->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classe supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting class: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la classe'
            ], 500);
        }
    }

    public function getLevelsBySchool(Request $request)
    {
        try {
            $currentAcademicYear = AcademicYear::where('is_current', true)->first();
            
            if (!$currentAcademicYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current academic year found'
                ], 400);
            }

            $schoolId = $request->query('school_id');
            
            $levels = Level::with('school')
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('is_active', true)
                ->when($schoolId, function($query, $schoolId) {
                    return $query->where('school_id', $schoolId);
                })
                ->orderBy('order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $levels,
                'message' => 'Levels retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching levels by school: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching levels'
            ], 500);
        }
    }
}