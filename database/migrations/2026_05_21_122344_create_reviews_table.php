<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // L'étudiant qui a laissé l'avis
            $table->unsignedTinyInteger('rating'); // Note de 1 à 5
            $table->text('comment')->nullable();   // Commentaire détaillé
            $table->boolean('is_approved')->default(false); // Modération
            $table->timestamps();

            // Un étudiant ne peut laisser qu'un seul avis par cours
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};