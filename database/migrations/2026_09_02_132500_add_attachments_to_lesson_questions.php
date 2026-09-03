<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            // Pièces jointes de la question (par l'étudiant)
            if (! Schema::hasColumn('lesson_questions', 'attachments')) {
                $table->json('attachments')->nullable()->after('body');
            }
            // Pièces jointes de la réponse de l'instructeur
            if (! Schema::hasColumn('lesson_questions', 'answer_attachments')) {
                $table->json('answer_attachments')->nullable()->after('answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_questions', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_questions', 'attachments')) {
                $table->dropColumn('attachments');
            }
            if (Schema::hasColumn('lesson_questions', 'answer_attachments')) {
                $table->dropColumn('answer_attachments');
            }
        });
    }
};
