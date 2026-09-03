<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── PAGES (CMS) ────────────────────────────────────────────
        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();   // HTML / GrapesJS JSON / blocks
                $table->string('template', 100)->default('default'); // default, landing, about, contact...
                $table->boolean('is_published')->default(false);
                $table->boolean('show_in_menu')->default(false);
                $table->unsignedInteger('menu_order')->default(0);
                $table->string('meta_title', 255)->nullable();
                $table->string('status', 255)->nullable();
                
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->string('featured_image')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index('is_published');
                $table->index('template');
            });
        }

        // ── ASSETS (médiathèque publique) ──────────────────────────
        if (! Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('original_name');
                $table->string('path');
                $table->string('url');
                $table->string('mime_type', 100);
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('alt_text', 255)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamps();

                $table->index('mime_type');
                $table->index('user_id');
            });
        }

        // ── STAGE REQUESTS (demandes de stage / formation) ─────────
        if (! Schema::hasTable('stage_requests')) {
            Schema::create('stage_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['internship', 'professional_training', 'academic_project', 'volunteering']);
                $table->string('institution_name')->nullable();    // école/université (si academic)
                $table->string('institution_address')->nullable();
                $table->string('field_of_study')->nullable();
                $table->string('level', 100)->nullable();         // L1, L2, M1, Master 2, Licence Pro, etc.
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('cv_path')->nullable();
                $table->string('cover_letter_path')->nullable();
                $table->string('motivation_letter_path')->nullable();
                $table->string('school_letter_path')->nullable(); // lettre de l'établissement
                $table->text('motivation_text')->nullable();
                $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'completed', 'cancelled'])->default('draft');
                $table->text('admin_notes')->nullable();
                $table->string('supervisor_name')->nullable();
                $table->string('supervisor_email')->nullable();
                $table->string('supervisor_phone', 30)->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'type']);
                $table->index('user_id');
            });
        }

        // ── ONBOARDING PROFILES (formations à l'inscription) ─────
        if (! Schema::hasTable('onboarding_profiles')) {
            Schema::create('onboarding_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('completed')->default(false);
                $table->json('interests')->nullable();         // catégories choisies
                $table->json('goals')->nullable();              // objectifs (carrière, certif, etc.)
                $table->string('education_level', 100)->nullable();
                $table->string('current_profession', 100)->nullable();
                $table->unsignedTinyInteger('experience_years')->nullable();
                $table->json('preferred_languages')->nullable();
                $table->json('preferred_categories')->nullable();
                $table->foreignId('first_course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_profiles');
        Schema::dropIfExists('stage_requests');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('pages');
    }
};
