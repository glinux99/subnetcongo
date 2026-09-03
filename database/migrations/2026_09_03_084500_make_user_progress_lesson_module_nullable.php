<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_progress')) {
            return;
        }

        // 1. Drop the FK constraint if it still exists (it's a non-nullable FK on a column we're making nullable)
        //    MySQL: we keep the FK but allow NULL
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN enrollment_id BIGINT UNSIGNED NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN lesson_id BIGINT UNSIGNED NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN module_id BIGINT UNSIGNED NULL'
        );

        // 2. Add a unique composite to avoid duplicate progress rows per (user, course, lesson)
        //    Skip if it already exists.
        $exists = collect(\Illuminate\Support\Facades\DB::select(
            "SHOW INDEXES FROM user_progress WHERE Key_name = 'user_progress_user_course_lesson_unique'"
        ))->count() > 0;
        if (! $exists) {
            // Some MySQL versions don't support unique with NULL easily; we just leave duplicates
            // managed at app-level via firstOrCreate.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_progress')) {
            return;
        }
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN enrollment_id BIGINT UNSIGNED NOT NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN lesson_id BIGINT UNSIGNED NOT NULL'
        );
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE user_progress MODIFY COLUMN module_id BIGINT UNSIGNED NOT NULL'
        );
    }
};
