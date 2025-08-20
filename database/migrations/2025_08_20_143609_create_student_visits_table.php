<?php
// database/migrations/xxxx_xx_xx_000005_create_student_visits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_visits', function (Blueprint $table) {
            $table->id();
            
            // Academic year reference
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            
            // Student basic info
            $table->string('student_first_name');
            $table->string('student_last_name');
            $table->enum('student_gender', ['FEMININ', 'MASCULIN']);
            $table->date('birth_date');
            $table->string('birth_place')->default('MARRAKECH');
            $table->string('current_school')->nullable();
            $table->string('current_level')->nullable();
            $table->string('city')->default('MARRAKECH');
            $table->boolean('is_repeating')->default(false);
            
            // Father info
            $table->string('father_first_name');
            $table->string('father_last_name');
            $table->string('father_phone');
            $table->string('father_email')->nullable();
            $table->string('father_profession')->nullable();
            
            // Mother info
            $table->string('mother_first_name');
            $table->string('mother_last_name');
            $table->string('mother_phone');
            $table->string('mother_email')->nullable();
            $table->string('mother_profession')->nullable();
            
            // Visit info
            $table->text('observations')->nullable();
            $table->datetime('visit_date');
            $table->datetime('test_date')->nullable();
            $table->enum('status', ['pending', 'test_scheduled', 'tested', 'accepted', 'rejected'])->default('pending');
            
            // School request
            $table->foreignId('requested_school_id')->constrained('schools');
            $table->foreignId('requested_level_id')->nullable()->constrained('levels');
            $table->string('student_file')->nullable(); // File reference
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_visits');
    }
};