<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('job_applications', 'applicant_id')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->renameColumn('applicant_id', 'user_id');
            });
        }

        $this->ensureUserForeignKey('user_id');
    }

    public function down(): void
    {
        $this->dropUserForeignKey('user_id');

        if (Schema::hasColumn('job_applications', 'user_id')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->renameColumn('user_id', 'applicant_id');
            });
        }

        $this->ensureUserForeignKey('applicant_id');
    }

    private function ensureUserForeignKey(string $column): void
    {
        $hasForeignKey = collect(Schema::getForeignKeys('job_applications'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                && $foreignKey['foreign_table'] === 'users');

        if (! $hasForeignKey) {
            Schema::table('job_applications', function (Blueprint $table) use ($column) {
                $table->foreign($column)
                    ->references('user_id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    private function dropUserForeignKey(string $column): void
    {
        $hasForeignKey = collect(Schema::getForeignKeys('job_applications'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                && $foreignKey['foreign_table'] === 'users');

        if ($hasForeignKey) {
            Schema::table('job_applications', function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
            });
        }
    }
};
