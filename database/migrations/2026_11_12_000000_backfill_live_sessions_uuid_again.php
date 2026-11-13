<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backfill des UUID manquants sur live_sessions (idempotent).
 *
 * La DB de dev a été reconstruite après un wipe accidentel : les sessions
 * créées/seedées depuis la migration d'origine ont un uuid NULL, ce qui
 * casse les liens (route binding implicite par uuid) et le rendu des
 * actions côté front. Sûr : ne touche que les lignes sans uuid.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('live_sessions')
            ->whereNull('uuid')
            ->select('id')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('live_sessions')
                    ->where('id', $row->id)
                    ->whereNull('uuid')
                    ->update(['uuid' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        // Pas de retour arrière : l'uuid est stable et sans effet métier.
    }
};
