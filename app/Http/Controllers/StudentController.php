<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentVisit;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['academicYear', 'class.level.school', 'studentVisit']);

        $academicYearId = $request->get('academic_year_id');
        if ($academicYearId) {
            $query->forYear($academicYearId);
        } else {
            $query->forCurrentYear();
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $query->byStatus($request->get('status'));
        }

        if ($request->has('class_id') && $request->get('class_id') !== '') {
            $query->byClass($request->get('class_id'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('father_first_name', 'like', "%{$search}%")
                  ->orWhere('father_last_name', 'like', "%{$search}%");
            });
        }

        $students = $query->active()
                         ->recent()
                         ->paginate($request->get('per_page', 15));

        return response()->json($students);
    }

    public function show(Student $student): JsonResponse
    {
        $student->load(['academicYear', 'school', 'level', 'class.level.school', 'studentVisit']);
        return response()->json($student);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birth_date' => 'required|date',
            'birth_place' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'father_first_name' => 'required|string|max:255',
            'father_last_name' => 'required|string|max:255',
            'father_phone' => 'required|string|max:255',
            'mother_first_name' => 'required|string|max:255',
            'mother_last_name' => 'required|string|max:255',
            'home_address' => 'required|string',
            'enrollment_date' => 'required|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'school_id' => 'nullable|exists:schools,id',
            'level_id' => 'nullable|exists:levels,id',
            'class_id' => 'nullable|exists:classes,id',
            'massar_code' => 'nullable|string|unique:students,massar_code',
            'student_code' => 'nullable|string|unique:students,student_code',
            'repeat_count' => 'nullable|integer|min:0',
            'nationality' => 'nullable|string|max:255',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'transport_method' => 'nullable|in:none,school_bus,private,walking',
            'meal_plan' => 'nullable|in:none,lunch_only,full_meals',
            'status' => 'nullable|in:active,suspended,graduated,transferred,dropped_out',
            'has_special_needs' => 'boolean',
            'special_needs_details' => 'nullable|string',
            'student_photo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->all();

        // Handle fields that cannot be null - set proper defaults
        if (array_key_exists('repeat_count', $data) && ($data['repeat_count'] === null || $data['repeat_count'] === '')) {
            $data['repeat_count'] = 0;
        }

        // Handle photo upload
        if ($request->hasFile('student_photo_file')) {
            $photo = $request->file('student_photo_file');
            $photoName = 'student_' . time() . '.' . $photo->getClientOriginalExtension();
            
            // Save to public/assets/images/students/
            $destinationPath = public_path('assets/images/students');
            $photo->move($destinationPath, $photoName);
            $data['student_photo'] = $photoName;
        }

        $student = Student::create($data);
        $student->load(['academicYear', 'school', 'level', 'class.level.school']);

        return response()->json($student, 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:male,female',
            'birth_date' => 'sometimes|required|date',
            'birth_place' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'father_first_name' => 'sometimes|required|string|max:255',
            'father_last_name' => 'sometimes|required|string|max:255',
            'father_phone' => 'sometimes|required|string|max:255',
            'mother_first_name' => 'sometimes|required|string|max:255',
            'mother_last_name' => 'sometimes|required|string|max:255',
            'home_address' => 'sometimes|required|string',
            'enrollment_date' => 'sometimes|required|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'school_id' => 'nullable|exists:schools,id',
            'level_id' => 'nullable|exists:levels,id',
            'class_id' => 'nullable|exists:classes,id',
            'massar_code' => [
                'nullable',
                'string',
                Rule::unique('students', 'massar_code')->ignore($student->id)
            ],
            'student_code' => [
                'nullable',
                'string',
                Rule::unique('students', 'student_code')->ignore($student->id)
            ],
            'repeat_count' => 'nullable|integer|min:0',
            'nationality' => 'nullable|string|max:255',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'transport_method' => 'nullable|in:none,school_bus,private,walking',
            'meal_plan' => 'nullable|in:none,lunch_only,full_meals',
            'status' => 'nullable|in:active,suspended,graduated,transferred,dropped_out',
            'has_special_needs' => 'boolean',
            'special_needs_details' => 'nullable|string',
            'student_photo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->all();

        // Handle fields that cannot be null - set proper defaults
        if (array_key_exists('repeat_count', $data) && ($data['repeat_count'] === null || $data['repeat_count'] === '')) {
            $data['repeat_count'] = 0;
        }

        // Handle photo upload
        if ($request->hasFile('student_photo_file')) {
            // Delete old photo if exists
            if ($student->student_photo) {
                $oldPhotoPath = public_path('assets/images/students/' . $student->student_photo);
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            
            $photo = $request->file('student_photo_file');
            $photoName = 'student_' . time() . '.' . $photo->getClientOriginalExtension();
            
            // Save to public/assets/images/students/
            $destinationPath = public_path('assets/images/students');
            $photo->move($destinationPath, $photoName);
            $data['student_photo'] = $photoName;
        }

        $student->update($data);
        $student->load(['academicYear', 'school', 'level', 'class.level.school']);

        return response()->json($student);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();
        return response()->json(['message' => 'Student deleted successfully']);
    }


    public function createFromVisit(Request $request): JsonResponse
    {
        $request->validate([
            'student_visit_id' => 'required|exists:student_visits,id',
            'class_id' => 'nullable|exists:class_rooms,id'
        ]);

        $visit = StudentVisit::findOrFail($request->student_visit_id);

        if ($visit->status !== 'accepted') {
            return response()->json([
                'message' => 'Only accepted visits can be converted to students'
            ], 422);
        }

        $existingStudent = Student::where('student_visit_id', $visit->id)->first();
        if ($existingStudent) {
            return response()->json([
                'message' => 'Student already created from this visit',
                'student' => $existingStudent
            ], 422);
        }

        $student = Student::createFromVisit($visit);
        
        if ($request->has('class_id')) {
            $student->update(['class_id' => $request->class_id]);
        }

        $student->load(['academicYear', 'school', 'level', 'class.level.school', 'studentVisit']);

        return response()->json($student, 201);
    }

    public function getAcceptedVisits(Request $request): JsonResponse
    {
        $query = StudentVisit::with(['academicYear', 'requestedSchool', 'requestedLevel'])
                            ->where('status', 'accepted')
                            ->whereDoesntHave('student');

        $academicYearId = $request->get('academic_year_id');
        if ($academicYearId) {
            $query->forYear($academicYearId);
        } else {
            $query->forCurrentYear();
        }

        $visits = $query->recent()->get();

        return response()->json($visits);
    }

    public function getClasses(Request $request): JsonResponse
    {
        $query = ClassRoom::with(['level.school', 'academicYear']);

        $academicYearId = $request->get('academic_year_id');
        if ($academicYearId) {
            $query->forYear($academicYearId);
        } else {
            $query->forCurrentYear();
        }

        // Filter by level if provided
        if ($request->has('level_id') && $request->get('level_id') !== '') {
            $query->where('level_id', $request->get('level_id'));
        }

        $classes = $query->orderBy('full_name')->get();

        return response()->json($classes);
    }

    public function stats(Request $request): JsonResponse
    {
        $academicYearId = $request->get('academic_year_id');
        
        $query = Student::query();
        if ($academicYearId) {
            $query->forYear($academicYearId);
        } else {
            $query->forCurrentYear();
        }

        $stats = [
            'total' => $query->active()->count(),
            'by_status' => $query->active()
                                ->selectRaw('status, count(*) as count')
                                ->groupBy('status')
                                ->pluck('count', 'status'),
            'by_gender' => $query->active()
                                ->selectRaw('gender, count(*) as count')
                                ->groupBy('gender')
                                ->pluck('count', 'gender'),
            'recent_enrollments' => $query->active()
                                         ->where('enrollment_date', '>=', now()->subDays(30))
                                         ->count()
        ];

        return response()->json($stats);
    }
}