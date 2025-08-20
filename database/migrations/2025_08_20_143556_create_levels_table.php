<?php
// database/migrations/xxxx_xx_xx_000003_create_levels_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('name'); // 1APIC, 2APIC, TC, 1BAC, etc.
            $table->string('code'); // Not unique because same code can exist in different years
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique combination per academic year
            $table->unique(['school_id', 'academic_year_id', 'code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('levels');
    }
};