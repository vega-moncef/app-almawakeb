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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('level_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Test name/title
            $table->string('description')->nullable();
            $table->enum('type', ['ORAL', 'ECRIT']); // Test type
            $table->string('test_file')->nullable(); // Uploaded test file
            $table->integer('duration_minutes'); // Test duration
            $table->decimal('total_marks', 8, 2)->default(20); // Total marks for the test
            $table->decimal('passing_marks', 8, 2)->default(10); // Minimum marks to pass
            $table->boolean('is_active')->default(true);
            $table->text('instructions')->nullable(); // Test instructions
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
