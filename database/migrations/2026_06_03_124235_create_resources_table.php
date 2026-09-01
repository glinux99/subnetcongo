<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cette table combine la logique polymorphique avec les besoins du Dashboard Executive.
     */
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();

            // --- [MORPHISME] ---
            // Génère resourceable_id et resourceable_type
            // Permet d'attacher un fichier à un Course, Lesson, Quiz, etc.
            $table->morphs('resourceable');

            // --- [IDENTITÉ DU FICHIER] ---
            $table->string('title'); // Nom d'affichage (ex: "Syllabus Réseaux v2")
            $table->string('slug')->nullable(); // Pour des accès URL propres
            $table->string('path'); // Chemin de stockage (ex: courses/files/abc.pdf)
            
            // --- [MÉTADONNÉES TECHNIQUES] ---
            $table->string('file_type', 20)->index(); // Extension (PDF, ZIP, EXE, MP4)
            $table->string('mime_type')->nullable(); // Type MIME (application/pdf)
            $table->unsignedBigInteger('size')->default(0)->comment('Taille en octets');
            
            // --- [FLAGS & ETATS] ---
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_external')->default(false)->comment('Si vrai, le path est une URL externe');
            $table->integer('sort_order')->default(0); // Pour le drag & drop dans l'éditeur
            
            // --- [AUDIT & ANALYTICS] ---
            // Savoir qui a uploadé et combien de fois c'est téléchargé
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('download_count')->default(0);

            // --- [SÉCURITÉ] ---
            $table->string('access_level')->default('student')->comment('guest, student, premium, admin');

            $table->timestamps();
            $table->softDeletes(); // Pour éviter les suppressions accidentelles de ressources critiques

            // Indexation pour les performances du Dashboard
            $table->index(['resourceable_type', 'resourceable_id', 'is_active'], 'res_search_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};