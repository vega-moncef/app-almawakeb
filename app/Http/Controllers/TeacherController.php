<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::with(['subjects', 'mainSubject', 'school', 'level']);
        
        // Search filters
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('type') && $request->get('type')) {
            $query->where('type', $request->get('type'));
        }
        
        
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
        
        $teachers = $query->orderBy('last_name')->orderBy('first_name')->paginate(15);
        
        return response()->json([
            'teachers' => $teachers,
            'types' => Teacher::getTypes()
        ]);
    }

    public function create()
    {
        $subjects = Subject::active()->ordered()->get();
        $schools = \App\Models\School::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'subjects' => $subjects,
            'schools' => $schools,
            'types' => Teacher::getTypes(),
            'contractTypes' => Teacher::getContractTypes(),
            'salaryTypes' => Teacher::getSalaryTypes()
        ]);
    }

    public function store(Request $request)
    {
        // Debug: Log the incoming request data
        \Log::info('Teacher creation request:', $request->all());
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:teachers,email',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date|before:today',
            'hire_date' => 'nullable|date',
            'type' => ['required', Rule::in([Teacher::TYPE_PERMANENT, Teacher::TYPE_VACATAIRE])],
            'qualification' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'subject_ids' => 'array',
            'subject_ids.*' => 'exists:subjects,id',
            // New fields
            'user_id' => 'nullable|exists:users,id',
            'employee_id' => 'nullable|string|unique:teachers,employee_id',
            'school_id' => 'nullable|exists:schools,id',
            'level_id' => 'nullable|exists:levels,id',
            'main_subject_id' => 'nullable|exists:subjects,id',
            'qualification_level' => 'nullable|string|max:255',
            'contract_type' => ['nullable', Rule::in([Teacher::CONTRACT_CDI, Teacher::CONTRACT_CDD, Teacher::CONTRACT_INTERIM, Teacher::CONTRACT_STAGE])],
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'max_hours_per_week' => 'nullable|integer|min:1|max:60',
            'salary_type' => ['nullable', Rule::in([Teacher::SALARY_MONTHLY, Teacher::SALARY_HOURLY])],
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'has_account' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher = Teacher::create($validated);
        
        if (!empty($validated['subject_ids'])) {
            $teacher->subjects()->sync($validated['subject_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
            'teacher' => $teacher->load('subjects')
        ], 201);
    }

    public function show(Teacher $teacher)
    {
        $teacher->load(['subjects', 'timetables.timeSlot', 'timetables.subject', 'timetables.classRoom']);
        
        return response()->json([
            'teacher' => $teacher
        ]);
    }

    public function edit(Teacher $teacher)
    {
        $teacher->load(['subjects', 'mainSubject', 'school', 'level']);
        $subjects = Subject::active()->ordered()->get();
        $schools = \App\Models\School::where('is_active', true)->orderBy('name')->get();
        
        return response()->json([
            'teacher' => $teacher,
            'subjects' => $subjects,
            'schools' => $schools,
            'types' => Teacher::getTypes(),
            'contractTypes' => Teacher::getContractTypes(),
            'salaryTypes' => Teacher::getSalaryTypes()
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('teachers', 'email')->ignore($teacher->id)],
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|date|before:today',
            'hire_date' => 'nullable|date',
            'type' => ['required', Rule::in([Teacher::TYPE_PERMANENT, Teacher::TYPE_VACATAIRE])],
            'qualification' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'subject_ids' => 'array',
            'subject_ids.*' => 'exists:subjects,id',
            // New fields
            'user_id' => 'nullable|exists:users,id',
            'employee_id' => ['nullable', 'string', Rule::unique('teachers', 'employee_id')->ignore($teacher->id)],
            'school_id' => 'nullable|exists:schools,id',
            'level_id' => 'nullable|exists:levels,id',
            'main_subject_id' => 'nullable|exists:subjects,id',
            'qualification_level' => 'nullable|string|max:255',
            'contract_type' => ['nullable', Rule::in([Teacher::CONTRACT_CDI, Teacher::CONTRACT_CDD, Teacher::CONTRACT_INTERIM, Teacher::CONTRACT_STAGE])],
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'max_hours_per_week' => 'nullable|integer|min:1|max:60',
            'salary_type' => ['nullable', Rule::in([Teacher::SALARY_MONTHLY, Teacher::SALARY_HOURLY])],
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'has_account' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        $teacher->update($validated);
        
        if (isset($validated['subject_ids'])) {
            $teacher->subjects()->sync($validated['subject_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
            'teacher' => $teacher->load('subjects')
        ]);
    }

    public function destroy(Teacher $teacher)
    {
        // Check if teacher has timetable entries
        if ($teacher->timetables()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete teacher with existing timetable entries. Please remove them first.'
            ], 422);
        }

        // Delete photo if exists
        if ($teacher->photo) {
            Storage::disk('public')->delete($teacher->photo);
        }

        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully'
        ]);
    }

    public function getAvailableTeachers(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'time_slot_id' => 'required|exists:time_slots,id', 
            'day_of_week' => 'required|integer|min:1|max:5',
            'academic_year_id' => 'required|exists:academic_years,id'
        ]);

        $teachers = Teacher::active()
            ->whereHas('subjects', function($query) use ($validated) {
                $query->where('subject_id', $validated['subject_id']);
            })
            ->whereDoesntHave('timetables', function($query) use ($validated) {
                $query->where('time_slot_id', $validated['time_slot_id'])
                      ->where('day_of_week', $validated['day_of_week'])
                      ->where('academic_year_id', $validated['academic_year_id']);
            })
            ->get();

        return response()->json(['teachers' => $teachers]);
    }
}
