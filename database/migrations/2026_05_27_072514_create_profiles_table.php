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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Informations personnelles & Étudiant
            $table->text('bio')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->enum('gender', ['M', 'F', 'Other'])->nullable();
            $table->date('birth_date')->nullable();
            
            // Statistiques d'apprentissage
            $table->integer('progress')->default(0);
            $table->integer('score')->default(0);
            $table->integer('certificates')->default(0);
            $table->integer('average_attendance')->default(0);
            $table->string('level')->default('Débutant');
            $table->string('avatar_path')->nullable();

            // Informations Instructeur
            $table->string('specialty')->nullable();
            $table->json('specialties')->nullable(); // Stockage multiple (JSON)
            $table->integer('experience_years')->default(0);
            $table->integer('commission_rate')->default(70);
            $table->string('linkedin_url')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('is_verified')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
