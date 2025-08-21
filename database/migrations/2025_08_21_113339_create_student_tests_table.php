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
        Schema::create('student_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'absent'])->default('assigned');
            $table->datetime('assigned_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->decimal('total_score', 8, 2)->nullable(); // Total score obtained
            $table->decimal('percentage', 5, 2)->nullable(); // Percentage score
            $table->boolean('passed')->nullable(); // Whether student passed
            $table->enum('admission_decision', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            // Ensure unique combination of student visit and test
            $table->unique(['student_visit_id', 'test_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_tests');
    }
};
