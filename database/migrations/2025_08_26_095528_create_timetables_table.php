<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->references('id')->on('classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 1=Monday, 2=Tuesday, ..., 5=Friday
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['academic_year_id', 'class_id', 'time_slot_id', 'day_of_week'], 'unique_class_schedule');
            $table->unique(['academic_year_id', 'teacher_id', 'time_slot_id', 'day_of_week'], 'unique_teacher_schedule');
            
            // Indexes for performance
            $table->index(['academic_year_id', 'class_id']);
            $table->index(['academic_year_id', 'teacher_id']);
            $table->index(['day_of_week', 'time_slot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
