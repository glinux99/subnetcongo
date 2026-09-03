<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_progress') && Schema::hasColumn('user_progress', 'enrollment_id')) {
            // Rend enrollment_id nullable : un user_progress peut être créé
            // indépendamment (parcours exploratoire, preview, etc.) sans qu'un
            // Enrollment formel existe en BDD.
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE user_progress MODIFY COLUMN enrollment_id BIGINT UNSIGNED NULL'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_progress') && Schema::hasColumn('user_progress', 'enrollment_id')) {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE user_progress MODIFY COLUMN enrollment_id BIGINT UNSIGNED NOT NULL'
            );
        }
    }
};
