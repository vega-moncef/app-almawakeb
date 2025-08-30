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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('type', ['permanent', 'vacataire'])->default('permanent');
            $table->text('qualification')->nullable();
            $table->integer('experience_years')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('photo')->nullable();
            
            // New fields
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('employee_id')->unique()->nullable();
            $table->foreignId('school_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('main_subject_id')->nullable()->constrained('subjects')->onDelete('set null'); // Subject ID
            $table->string('qualification_level')->nullable();
            $table->enum('contract_type', ['cdi', 'cdd', 'interim', 'stage'])->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->integer('max_hours_per_week')->default(40);
            $table->enum('salary_type', ['monthly', 'hourly'])->default('monthly');
            $table->decimal('hourly_rate', 8, 2)->nullable(); // For vacataire
            $table->decimal('monthly_salary', 10, 2)->nullable(); // For permanent
            $table->boolean('has_account')->default(false);
            $table->timestamp('account_created_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('employee_id');
            $table->index(['school_id', 'main_subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
