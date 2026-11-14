<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'has_quiz')) {
                $table->boolean('has_quiz')->default(false)->after('has_document');
            }

            if (! Schema::hasColumn('modules', 'quiz_required')) {
                $table->boolean('quiz_required')->default(true)->after('has_quiz');
            }

            if (! Schema::hasColumn('modules', 'document_name')) {
                $table->string('document_name')->nullable()->after('document_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'document_name')) {
                $table->dropColumn('document_name');
            }
            if (Schema::hasColumn('modules', 'quiz_required')) {
                $table->dropColumn('quiz_required');
            }
            if (Schema::hasColumn('modules', 'has_quiz')) {
                $table->dropColumn('has_quiz');
            }
        });
    }
};
