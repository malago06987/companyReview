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
        Schema::create('reviews', function (Blueprint $table) {
        $table->id('review_id'); // Primary Key

        // Foreign Keys
        $table->foreignId('company_id')
              ->constrained('companies', 'company_id')
              ->onDelete('cascade');

        $table->foreignId('user_id')
              ->constrained('users', 'user_id')
              ->onDelete('cascade');

        $table->unsignedTinyInteger('rating_life')->default(0);
        $table->unsignedTinyInteger('rating_work')->default(0);
        $table->unsignedTinyInteger('rating_money')->default(0);
        $table->unsignedTinyInteger('rating_society')->default(0);

        $table->text('review_text');
        $table->string('status')->default('pending'); // เช่น pending, approved, rejected
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
