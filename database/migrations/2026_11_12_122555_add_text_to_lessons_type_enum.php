<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE lessons MODIFY `type` ENUM('video','document','quiz','assignment','text') NOT NULL DEFAULT 'video'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE lessons MODIFY `type` ENUM('video','document','quiz','assignment') NOT NULL DEFAULT 'video'");
    }
};
