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
       Schema::create('companies', function (Blueprint $table) {
        $table->id('company_id'); // Primary Key
        $table->string('company_name');
        $table->string('logo_image')->nullable();
        $table->text('description')->nullable();

        // Foreign Key เชื่อมไปที่ตาราง industries
        $table->foreignId('industry_id')
              ->constrained('industries', 'industry_id')
              ->onDelete('cascade');

        $table->text('address')->nullable();
        $table->text('benefits')->nullable();
        $table->text('culture')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
