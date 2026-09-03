<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ces tables remplacent le stockage JSON dans web_settings.
     * Plus simples à éditer depuis un back-office avec relations,
     * drag-drop d'ordre, suppression, etc.
     */
    public function up(): void
    {
        if (! Schema::hasTable('landing_slides')) {
            Schema::create('landing_slides', function (Blueprint $t) {
                $t->id();
                $t->string('badge', 100)->nullable();
                $t->string('title', 255);
                $t->string('highlight', 255)->nullable();
                $t->text('description');
                $t->string('bg_image', 500)->nullable();
                $t->string('main_image', 500)->nullable();
                $t->string('icon', 50)->default('pi-globe');
                $t->string('card_title', 255)->nullable();
                $t->string('card_sub', 255)->nullable();
                $t->string('cta_label', 100)->nullable();
                $t->string('cta_url', 500)->nullable();
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['is_active', 'position']);
            });
        }

        if (! Schema::hasTable('landing_technologies')) {
            Schema::create('landing_technologies', function (Blueprint $t) {
                $t->id();
                $t->string('name', 100);
                $t->string('slug', 100)->unique();
                $t->string('icon', 100)->nullable();       // emoji, url image, ou classe pi pi-*
                $t->string('color', 20)->default('#003399'); // couleur d'accent
                $t->string('category', 100)->nullable();   // Réseau, Cloud, Cybersécurité, etc.
                $t->text('description')->nullable();
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['is_active', 'position']);
            });
        }

        if (! Schema::hasTable('landing_stats')) {
            Schema::create('landing_stats', function (Blueprint $t) {
                $t->id();
                $t->string('label', 100);
                $t->string('value', 50);                // ex: "15K+", "850+", "24/7"
                $t->string('icon', 50)->default('pi-chart-bar');
                $t->string('color', 20)->default('#003399');
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['is_active', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_stats');
        Schema::dropIfExists('landing_technologies');
        Schema::dropIfExists('landing_slides');
    }
};
