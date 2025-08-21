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
        Schema::create('student_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 8, 2); // Score obtained in this subject
            $table->decimal('max_score', 8, 2); // Maximum score for this subject
            $table->text('remarks')->nullable(); // Teacher remarks for this subject
            $table->timestamps();
            
            // Ensure unique combination of student test and subject
            $table->unique(['student_test_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_test_results');
    }
};
