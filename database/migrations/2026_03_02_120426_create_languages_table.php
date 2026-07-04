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
    Schema::create('languages', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // ex: Français
        $table->string('code')->unique(); // ex: fr
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        // Souvent pas besoin de timestamps pour les langues
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
