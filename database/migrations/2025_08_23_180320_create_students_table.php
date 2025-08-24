<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
            $table->foreignId('student_visit_id')->nullable()->constrained('student_visits')->onDelete('set null');
            // $table->foreignId('admission_test_id')->nullable()->constrained('admission_tests')->onDelete('set null');
            
            // BASIC STUDENT INFORMATION
            $table->string('student_code')->unique(); // Generated student ID
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->string('birth_place');
            $table->string('city');
            $table->integer('repeat_count')->default(0);
            $table->string('previous_school')->nullable();
            $table->string('previous_level')->nullable();
            
            // OFFICIAL IDENTIFICATION
            $table->string('massar_code')->nullable()->unique();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('nationality')->default('Moroccan');
            
            // FATHER INFORMATION
            $table->string('father_first_name');
            $table->string('father_last_name');
            $table->string('father_phone');
            $table->string('father_email')->nullable();
            $table->string('father_profession')->nullable();
            $table->string('father_national_id')->nullable();
            
            // MOTHER INFORMATION
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_phone')->nullable();
            $table->string('mother_email')->nullable();
            $table->string('mother_profession')->nullable();
            $table->string('mother_national_id')->nullable();
            
            // ADDRESS INFORMATION
            $table->text('home_address');
            $table->string('postal_code')->nullable();
            $table->string('neighborhood')->nullable();
            
            // EMERGENCY CONTACT
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // MEDICAL INFORMATION
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->boolean('has_special_needs')->default(false);
            $table->text('special_needs_details')->nullable();
            $table->text('dietary_restrictions')->nullable();
            
            // SCHOOL SERVICES
            $table->enum('transport_method', ['none', 'school_bus', 'private', 'walking'])->default('none');
            $table->string('bus_route')->nullable();
            $table->enum('meal_plan', ['none', 'lunch_only', 'full_meals'])->default('none');
            
            // ACADEMIC INFORMATION
            $table->date('enrollment_date');
            $table->decimal('admission_score', 5, 2)->nullable();
            $table->enum('status', ['active', 'suspended', 'graduated', 'transferred', 'dropped_out'])->default('active');
            $table->text('observations')->nullable();
            
            // FILES & DOCUMENTS
            $table->string('student_photo')->nullable();
            $table->json('documents')->nullable(); // Store document paths
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // INDEXES for better performance
            $table->index(['academic_year_id', 'status']);
            $table->index(['class_id', 'status']);
            $table->index('student_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
