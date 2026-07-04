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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();

            // --- Design & Couleurs ---
            $table->string('background_color', 20)->default('#ffffff');
            $table->integer('bg_opacity')->default(100);
            $table->string('border_color', 20)->default('#c5a059');
            $table->integer('border_width')->default(20);
            $table->string('text_color', 20)->default('#1e293b');
            
            // --- Géométrie & Typographie ---
            $table->enum('orientation', ['landscape', 'portrait'])->default('landscape');
            $table->string('primary_font')->default('Montserrat');
            $table->string('accent_font')->default('Playfair Display');

            // --- Options d'Affichage (Toggles) ---
            $table->boolean('show_qr')->default(true);
            $table->boolean('show_signature')->default(true);
            $table->boolean('show_logo')->default(false);
            $table->boolean('show_background')->default(false);

            // --- Paramètres des Médias (Positions & Échelles) ---
            $table->integer('logo_scale')->default(15);
            $table->integer('logo_top')->default(10);
            $table->integer('background_image_scale')->default(100);
            $table->integer('background_opacity')->default(20);

            // --- Chemins des Fichiers (Storage) ---
            $table->string('signature_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('background_image_path')->nullable();

            // --- Données de Configuration (Positions des textes, etc.) ---
            $table->json('config')->nullable()->comment('Contient les positions, tailles et couleurs des blocs de texte');

            // --- Meta & Statuts ---
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes(); // Optionnel : pour garder une trace des vieux designs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};