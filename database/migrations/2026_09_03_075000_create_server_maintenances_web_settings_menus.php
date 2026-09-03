<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('server_maintenances')) {
            Schema::create('server_maintenances', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type', 50)->default('update'); // security, performance, update, backup, database
                $table->string('severity', 20)->default('medium'); // low, medium, high, critical
                $table->string('status', 30)->default('pending'); // pending, running, completed, failed, cancelled
                $table->timestamp('scheduled_at');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->json('affected_services')->nullable();
                $table->string('downtime_estimate', 50)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index('status');
                $table->index('scheduled_at');
            });
        }

        // ── web_settings (clé/valeur pour identité site, contact, social, SEO) ─
        if (! Schema::hasTable('web_settings')) {
            Schema::create('web_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->longText('value')->nullable();
                $table->string('group', 50)->default('general'); // general, contact, social, seo, theme, hero, about, academy
                $table->string('type', 20)->default('text'); // text, html, json, image, url
                $table->timestamps();
                $table->index('group');
            });
        }

        // ── menus (déjà utilisé par App\Models\Menu) ─────────────────────────
        if (! Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('location', 50)->default('header'); // header, footer, sidebar
                $table->json('items')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('location');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('web_settings');
        Schema::dropIfExists('server_maintenances');
    }
};
