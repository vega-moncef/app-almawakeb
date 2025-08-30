<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimeSlotController extends Controller
{
    public function index(Request $request)
    {
        $query = TimeSlot::query();
        
        // Filter by scope
        if ($request->has('school_id')) {
            $query->forSchool($request->school_id);
        }
        
        if ($request->has('level_id')) {
            $query->forLevel($request->level_id);
        }
        
        if ($request->has('class_id')) {
            $query->forClass($request->class_id);
        }
        
        // Show global time slots if no specific scope is requested
        if (!$request->has('school_id') && !$request->has('level_id') && !$request->has('class_id')) {
            if ($request->get('scope') === 'global') {
                $query->global();
            }
            // If no scope specified, show all time slots
        }
        
        // Filter by type (regular class or break)
        if ($request->has('type')) {
            if ($request->type === 'regular') {
                $query->where('is_break', false);
            } elseif ($request->type === 'break') {
                $query->where('is_break', true);
            }
        }
        
        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        $timeSlots = $query->with(['classRoom.level', 'school', 'level'])->withCount('timetables')->ordered()->get();
        
        return response()->json([
            'success' => true,
            'timeSlots' => $timeSlots,
            'total' => $timeSlots->count()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_break' => 'boolean',
            'break_type' => 'nullable|string|in:recreation,lunch,morning_break,afternoon_break',
            'school_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
            'class_id' => 'nullable|exists:class_rooms,id'
        ]);

        // Set default values
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_break'] = $validated['is_break'] ?? false;
        
        // Auto-generate order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = TimeSlot::max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        // Check for time conflicts within the same scope
        $conflictingSlot = $this->checkTimeConflicts(
            $validated['start_time'], 
            $validated['end_time'],
            null,
            $validated['school_id'] ?? null,
            $validated['level_id'] ?? null,
            $validated['class_id'] ?? null
        );
        
        if ($conflictingSlot) {
            return response()->json([
                'success' => false,
                'message' => "Time conflict with existing slot: {$conflictingSlot->name} ({$conflictingSlot->display_time})"
            ], 422);
        }

        $timeSlot = TimeSlot::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Time slot created successfully',
            'timeSlot' => $timeSlot
        ], 201);
    }

    public function show(TimeSlot $timeSlot)
    {
        return response()->json([
            'success' => true,
            'timeSlot' => $timeSlot
        ]);
    }

    public function update(Request $request, TimeSlot $timeSlot)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'order' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'is_break' => 'boolean',
            'break_type' => 'nullable|string|in:recreation,lunch,morning_break,afternoon_break',
            'school_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
            'class_id' => 'nullable|exists:class_rooms,id'
        ]);

        // Set default values
        $validated['is_active'] = $validated['is_active'] ?? $timeSlot->is_active;
        $validated['is_break'] = $validated['is_break'] ?? $timeSlot->is_break;

        // Check for time conflicts (excluding current slot)
        $conflictingSlot = $this->checkTimeConflicts(
            $validated['start_time'], 
            $validated['end_time'],
            $timeSlot->id,
            $validated['school_id'] ?? null,
            $validated['level_id'] ?? null,
            $validated['class_id'] ?? null
        );
        
        if ($conflictingSlot) {
            return response()->json([
                'success' => false,
                'message' => "Time conflict with existing slot: {$conflictingSlot->name} ({$conflictingSlot->display_time})"
            ], 422);
        }

        $timeSlot->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Time slot updated successfully',
            'timeSlot' => $timeSlot->fresh()
        ]);
    }

    public function destroy(TimeSlot $timeSlot)
    {
        // Check if time slot is being used in timetables
        $timetableCount = $timeSlot->timetables()->count();
        
        if ($timetableCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete time slot. It is being used in {$timetableCount} timetable entries."
            ], 422);
        }

        $timeSlot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Time slot deleted successfully'
        ]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'timeSlots' => 'required|array',
            'timeSlots.*.id' => 'required|exists:time_slots,id',
            'timeSlots.*.order' => 'required|integer|min:1'
        ]);

        foreach ($validated['timeSlots'] as $slotData) {
            TimeSlot::where('id', $slotData['id'])
                   ->update(['order' => $slotData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Time slots reordered successfully'
        ]);
    }

    public function toggle(TimeSlot $timeSlot)
    {
        $timeSlot->update([
            'is_active' => !$timeSlot->is_active
        ]);

        $status = $timeSlot->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Time slot {$status} successfully",
            'timeSlot' => $timeSlot
        ]);
    }

    public function getBreakTypes()
    {
        return response()->json([
            'success' => true,
            'breakTypes' => [
                'recreation' => 'Récréation',
                'lunch' => 'Pause déjeuner',
                'morning_break' => 'Pause matinale',
                'afternoon_break' => 'Pause après-midi'
            ]
        ]);
    }

    private function checkTimeConflicts($startTime, $endTime, $excludeId = null, $schoolId = null, $levelId = null, $classId = null)
    {
        $query = TimeSlot::where('is_active', true)
            ->where(function($q) use ($startTime, $endTime) {
                $q->where(function($subQ) use ($startTime, $endTime) {
                    // New slot starts during existing slot
                    $subQ->where('start_time', '<=', $startTime)
                         ->where('end_time', '>', $startTime);
                })->orWhere(function($subQ) use ($startTime, $endTime) {
                    // New slot ends during existing slot
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>=', $endTime);
                })->orWhere(function($subQ) use ($startTime, $endTime) {
                    // New slot completely contains existing slot
                    $subQ->where('start_time', '>=', $startTime)
                         ->where('end_time', '<=', $endTime);
                });
            });

        // Only check conflicts within the same scope
        $query->where(function($q) use ($schoolId, $levelId, $classId) {
            if ($schoolId) {
                $q->where('school_id', $schoolId);
            } elseif ($levelId) {
                $q->where('level_id', $levelId)->whereNull('school_id');
            } elseif ($classId) {
                $q->where('class_id', $classId)->whereNull('level_id')->whereNull('school_id');
            } else {
                // For global time slots, check conflicts with other global slots
                $q->whereNull('school_id')->whereNull('level_id')->whereNull('class_id');
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }
}