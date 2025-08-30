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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "8h30-9h25", "9h25-10h20"
            $table->time('start_time'); // e.g., 08:30
            $table->time('end_time'); // e.g., 09:25
            $table->integer('order'); // For ordering time slots
            $table->boolean('is_active')->default(true);
            $table->boolean('is_break')->default(false); // For break periods like 10h20-10h40
            $table->string('break_type')->nullable(); // Type of break (recreation, lunch, etc.)
            $table->unsignedBigInteger('school_id')->nullable(); // Optional: specific to school
            $table->unsignedBigInteger('level_id')->nullable(); // Optional: specific to level
            $table->unsignedBigInteger('class_id')->nullable(); // Optional: specific to class
            $table->timestamps();
            
            $table->index(['order', 'is_active']);
            $table->index(['school_id', 'level_id', 'class_id'], 'time_slots_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
