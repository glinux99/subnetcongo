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
        // Rendre enrolled_at nullable : pour les demandes PENDING/SUSPENDED,
        // la date d'inscription effective n'est pas encore connue.
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE enrollments MODIFY enrolled_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE enrollments MODIFY enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
};
