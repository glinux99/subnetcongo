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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // 1. IDENTIFICATION DE L'ACTEUR
            // Nullable car une action peut être générée par le "Système" (CRON, Task Scheduling)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('impersonated_by')->nullable()->constrained('users')->onDelete('set null'); // Si un admin agit à la place d'un élève

            // 2. NATURE DE L'ÉVÉNEMENT
            
            $table->string('event_name')->nullable()->default("new event")->index(); // ex: COURSE_PUBLISHED, LOGIN_FAILED
            $table->string('event')->nullable()->default("new event"); // ex: COURSE_PUBLISHED, LOGIN_FAILED
            $table->string('level')->default('info')->index(); // info, warning, error, critical (Indispensable pour votre Dashboard)
            $table->text('description')->nullable(); // Phrase lisible : "L'utilisateur X a modifié le cours Y"
            
            // 3. POLYMORPHISME (Cible de l'action)
            // Permet de lier le log à n'importe quel modèle (Course, Lesson, User, etc.)
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();

            // 4. PAYLOAD (Données)
            $table->json('old_values')->nullable(); // État avant modification
            $table->json('new_values')->nullable(); // État après modification
            $table->json('metadata')->nullable();   // Infos sup: ID de transaction, IDs de fichiers, etc.

            // 5. CONTEXTE RÉSEAU & SÉCURITÉ
            $table->string('url', 1024)->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('locale', 10)->nullable(); // fr, en...
            
            // 6. PERFORMANCE & INFRA
            $table->decimal('execution_time', 8, 4)->nullable(); // Temps en ms pour exécuter l'action
            $table->unsignedBigInteger('memory_usage')->nullable(); // Mémoire consommée en octets
            
            // 7. FLAGS INTELLIGENTS
            $table->boolean('is_suspicious')->default(false)->index(); // Marqueur pour l'IA/Dashboard de sécurité
            $table->string('tags')->nullable(); // Pour filtrage rapide : "finance", "security", "academic"

            $table->timestamps();

            // Indexation pour les rapports de performance du Dashboard
            $table->index(['created_at', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};