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
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->integer('hours_per_week')->nullable(); // Optional: hours allocated per week for this subject in this class
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique combination of class and subject
            $table->unique(['class_id', 'subject_id']);
            
            // Add indexes for performance
            $table->index(['class_id', 'is_active']);
            $table->index(['subject_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject');
    }
};
