<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('job_id')
                ->constrained('jobs', 'job_id')
                ->cascadeOnDelete();
            $table->foreignId('applicant_id')
                ->constrained('users', 'user_id')
                ->cascadeOnDelete();
            $table->string('resume_path');
            $table->text('cover_letter')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['job_id', 'applicant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
