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
        $hasOldColumn = Schema::hasColumn('jobs', 'authorization_document_path');
        $hasNewColumn = Schema::hasColumn('jobs', 'document');

        if ($hasOldColumn && ! $hasNewColumn) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->renameColumn('authorization_document_path', 'document');
            });

            return;
        }

        if (! $hasOldColumn && $hasNewColumn) {
            return;
        }

        throw new RuntimeException('Cannot rename jobs document column: expected exactly one of authorization_document_path or document.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasOldColumn = Schema::hasColumn('jobs', 'authorization_document_path');
        $hasNewColumn = Schema::hasColumn('jobs', 'document');

        if (! $hasOldColumn && $hasNewColumn) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->renameColumn('document', 'authorization_document_path');
            });

            return;
        }

        if ($hasOldColumn && ! $hasNewColumn) {
            return;
        }

        throw new RuntimeException('Cannot restore jobs document column: expected exactly one of authorization_document_path or document.');
    }
};
