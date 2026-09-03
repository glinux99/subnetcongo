<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── web_settings ──
        if (! Schema::hasTable('web_settings')) {
            Schema::create('web_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->longText('value')->nullable();
                $table->string('group', 50)->default('general');
                $table->string('type', 20)->default('text');
                $table->timestamps();
                $table->index('group');
            });
        } else {
            Schema::table('web_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('web_settings', 'key'))   $table->string('key', 100)->unique();
                if (! Schema::hasColumn('web_settings', 'value')) $table->longText('value')->nullable();
                if (! Schema::hasColumn('web_settings', 'group')) $table->string('group', 50)->default('general');
                if (! Schema::hasColumn('web_settings', 'type'))  $table->string('type', 20)->default('text');
                $table->index('group', 'web_settings_group_index');
            });
        }

        // ── menus ──
        if (! Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('location', 50)->default('header');
                $table->json('items')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('location');
            });
        } else {
            Schema::table('menus', function (Blueprint $table) {
                if (! Schema::hasColumn('menus', 'name'))     $table->string('name', 100);
                if (! Schema::hasColumn('menus', 'location')) $table->string('location', 50)->default('header');
                if (! Schema::hasColumn('menus', 'items'))    $table->json('items')->nullable();
                if (! Schema::hasColumn('menus', 'is_active'))$table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('web_settings');
    }
};
