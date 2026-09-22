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
       Schema::create('jobs', function (Blueprint $table) {
        $table->id('job_id'); // Primary Key

        // Foreign Keys
        $table->foreignId('company_id')
              ->constrained('companies', 'company_id')
              ->onDelete('cascade');

        $table->foreignId('function_id')
              ->constrained('job_functions', 'function_id')
              ->onDelete('cascade');

        $table->string('job_title');
        $table->text('job_description');
        $table->string('salary')->nullable();
        $table->string('work_location')->nullable();
        $table->string('employment_type')->nullable(); // เช่น Full-time, Part-time
        $table->string('status')->default('open'); // เช่น open, closed
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
