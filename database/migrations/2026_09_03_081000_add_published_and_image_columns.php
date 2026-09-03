<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── realizations : ajout is_published, image, summary, description, location, etc.
        if (Schema::hasTable('realizations')) {
            Schema::table('realizations', function (Blueprint $t) {
                if (! Schema::hasColumn('realizations', 'is_published'))   $t->boolean('is_published')->default(false)->after('id');
                if (! Schema::hasColumn('realizations', 'title'))           $t->string('title')->after('id');
                if (! Schema::hasColumn('realizations', 'slug'))            $t->string('slug')->unique()->nullable();
                if (! Schema::hasColumn('realizations', 'summary'))         $t->text('summary')->nullable();
                if (! Schema::hasColumn('realizations', 'description'))     $t->longText('description')->nullable();
                if (! Schema::hasColumn('realizations', 'image'))           $t->string('image')->nullable();
                if (! Schema::hasColumn('realizations', 'cover_image'))     $t->string('cover_image')->nullable();
                if (! Schema::hasColumn('realizations', 'client'))          $t->string('client')->nullable();
                if (! Schema::hasColumn('realizations', 'location'))        $t->string('location')->nullable();
                if (! Schema::hasColumn('realizations', 'completed_at'))    $t->date('completed_at')->nullable();
                if (! Schema::hasColumn('realizations', 'category'))        $t->string('category')->nullable();
                if (! Schema::hasColumn('realizations', 'tags'))            $t->json('tags')->nullable();
            });
        }

        // ── testimonials : ajout name, role, avatar, message
        if (Schema::hasTable('testimonials')) {
            Schema::table('testimonials', function (Blueprint $t) {
                if (! Schema::hasColumn('testimonials', 'name'))    $t->string('name')->nullable();
                if (! Schema::hasColumn('testimonials', 'author_name'))    $t->string('author_name')->nullable();
                if (! Schema::hasColumn('testimonials', 'role'))    $t->string('role')->nullable();
                if (! Schema::hasColumn('testimonials', 'avatar'))  $t->string('avatar')->nullable();
                if (! Schema::hasColumn('testimonials', 'author_avatar'))  $t->string('author_avatar')->nullable();
                if (! Schema::hasColumn('testimonials', 'message')) $t->longText('message')->nullable();
                if (! Schema::hasColumn('testimonials', 'company'))$t->string('company')->nullable();
            });
        }

        // ── blog_posts : ajout excerpt, featured_image
        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $t) {
                if (! Schema::hasColumn('blog_posts', 'excerpt'))        $t->text('excerpt')->nullable();
                if (! Schema::hasColumn('blog_posts', 'featured_image'))$t->string('featured_image')->nullable();
                if (! Schema::hasColumn('blog_posts', 'category_id'))   $t->unsignedBigInteger('category_id')->nullable();
                if (! Schema::hasColumn('blog_posts', 'is_published'))  $t->boolean('is_published')->default(false);
            });
        }
    }

    public function down(): void
    {
        // no-op
    }
};
