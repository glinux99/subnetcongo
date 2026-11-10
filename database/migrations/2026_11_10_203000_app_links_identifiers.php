<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1) UUID sur live_sessions (lien partageable propre)
        if (! Schema::hasColumn('live_sessions', 'uuid')) {
            Schema::table('live_sessions', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }
        DB::table('live_sessions')->whereNull('uuid')->select('id')->orderBy('id')->each(function ($row) {
            DB::table('live_sessions')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        // 2) UUID sur exam_results (lien de résultat propre)
        if (! Schema::hasColumn('exam_results', 'uuid')) {
            Schema::table('exam_results', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }
        DB::table('exam_results')->whereNull('uuid')->select('id')->orderBy('id')->each(function ($row) {
            DB::table('exam_results')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        });

        // 3) Backfill défensif des colonnes d'identifiants qui peuvent être NULL/vides
        foreach (['courses', 'modules', 'lessons', 'learning_paths'] as $table) {
            if (Schema::hasColumn($table, 'slug')) {
                DB::statement("UPDATE {$table} SET slug = CONCAT('{$table}-', id) WHERE slug IS NULL OR slug = ''");
            }
        }
        foreach (['exams', 'exam_attempts', 'certificates', 'questions'] as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                DB::table($table)->whereNull('uuid')->select('id')->orderBy('id')->each(function ($row) use ($table) {
                    DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                });
            }
        }

        // 4) Liens de ticket en minuscules
        DB::statement('UPDATE support_tickets SET ticket_number = LOWER(ticket_number)');

        // 5) Codes manquants (cohortes / régions) — identifiant auto pour liens
        foreach (['cohorts' => 'coh', 'regions' => 'reg'] as $table => $prefix) {
            if (Schema::hasColumn($table, 'code')) {
                DB::statement("UPDATE {$table} SET code = CONCAT('{$prefix}-', id) WHERE code IS NULL OR code = ''");
            }
        }
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
