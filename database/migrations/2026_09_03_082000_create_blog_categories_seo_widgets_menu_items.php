<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── blog_categories ──────────────────────────────────────
        if (! Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('order')->default(0);
                $t->timestamps();
            });
        }

        // ── seo_settings ─────────────────────────────────────────
        if (! Schema::hasTable('seo_settings')) {
            Schema::create('seo_settings', function (Blueprint $t) {
                $t->id();
                $t->string('key')->unique();
                $t->longText('value')->nullable();
                $t->string('type', 30)->default('setting'); // setting, rule, redirect
                $t->string('url_pattern')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index('type');
            });
        }

        // ── widgets ──────────────────────────────────────────────
        if (! Schema::hasTable('widgets')) {
            Schema::create('widgets', function (Blueprint $t) {
                $t->id();
                $t->string('title')->nullable();
                $t->string('type', 30); // html, menu, recent_posts, newsletter, image
                $t->string('zone', 50); // sidebar_left, sidebar_right, footer_1..3, homepage_top
                $t->json('content')->nullable();
                $t->unsignedInteger('position')->default(0);
                $t->unsignedInteger('order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['zone', 'is_active']);
            });
        }

        // ── menu_items ───────────────────────────────────────────
        if (! Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
                $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
                $t->string('title');
                $t->string('url')->nullable();
                $t->string('target', 20)->default('_self');
                $t->string('icon', 50)->nullable();
                $t->unsignedInteger('order')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['menu_id', 'parent_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('blog_categories');
    }
};
