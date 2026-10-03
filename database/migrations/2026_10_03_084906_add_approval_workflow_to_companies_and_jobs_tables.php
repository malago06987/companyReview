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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'user_id')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['user_id', 'approval_status', 'rejection_reason']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['user_id', 'approval_status', 'rejection_reason']);
        });
    }
};
