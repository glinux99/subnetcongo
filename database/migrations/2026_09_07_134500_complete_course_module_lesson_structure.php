<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Courses: add course-level intro video and certificate settings
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'intro_video_url')) {
                $table->string('intro_video_url', 500)->nullable()->after('preview_video_url');
            }
            if (!Schema::hasColumn('courses', 'intro_video_title')) {
                $table->string('intro_video_title')->nullable()->after('intro_video_url');
            }
            if (!Schema::hasColumn('courses', 'certificate_template_id')) {
                $table->unsignedBigInteger('certificate_template_id')->nullable()->after('certificate_enabled');
            }
        });

        // Modules: complete the module structure
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'intro_video_title')) {
                $table->string('intro_video_title')->nullable()->after('intro_video_url');
            }
            if (!Schema::hasColumn('modules', 'text_content')) {
                $table->longText('text_content')->nullable()->after('description');
            }
            if (!Schema::hasColumn('modules', 'objectives')) {
                $table->json('objectives')->nullable()->after('text_content');
            }
            if (!Schema::hasColumn('modules', 'has_document')) {
                $table->boolean('has_document')->default(false)->after('document_url');
            }
            if (!Schema::hasColumn('modules', 'document_title')) {
                $table->string('document_title')->nullable()->after('has_document');
            }
            if (!Schema::hasColumn('modules', 'order_in_course')) {
                $table->integer('order_in_course')->default(0)->after('order');
            }
        });

        // Lessons: add content type and sequence
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'lesson_type')) {
                $table->string('lesson_type', 50)->default('video')->after('type');
            }
            if (!Schema::hasColumn('lessons', 'content_text')) {
                $table->longText('content_text')->nullable()->after('content');
            }
            if (!Schema::hasColumn('lessons', 'content_html')) {
                $table->longText('content_html')->nullable()->after('content_text');
            }
            if (!Schema::hasColumn('lessons', 'video_duration')) {
                $table->integer('video_duration')->nullable()->comment('Duration in seconds')->after('video_url');
            }
            if (!Schema::hasColumn('lessons', 'has_attachment')) {
                $table->boolean('has_attachment')->default(false)->after('attachment_path');
            }
            if (!Schema::hasColumn('lessons', 'attachment_title')) {
                $table->string('attachment_title')->nullable()->after('has_attachment');
            }
            if (!Schema::hasColumn('lessons', 'is_sequential')) {
                $table->boolean('is_sequential')->default(true)->after('is_free_preview');
            }
        });

        // Module Quiz tracking
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'quiz_passing_score')) {
                $table->decimal('quiz_passing_score', 5, 2)->default(50)->after('quiz_weight');
            }
            if (!Schema::hasColumn('modules', 'quiz_attempts_allowed')) {
                $table->integer('quiz_attempts_allowed')->default(3)->after('quiz_passing_score');
            }
        });

        // Create module_completions table for tracking
        if (!Schema::hasTable('module_completions')) {
            Schema::create('module_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('module_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->boolean('intro_video_completed')->default(false);
                $table->boolean('lessons_completed')->default(false);
                $table->boolean('quiz_completed')->default(false);
                $table->boolean('document_downloaded')->default(false);
                $table->boolean('is_completed')->default(false);
                $table->decimal('quiz_score', 5, 2)->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'module_id']);
                $table->index(['user_id', 'course_id']);
            });
        }

        // Create lesson_completions table
        if (!Schema::hasTable('lesson_completions')) {
            Schema::create('lesson_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
                $table->foreignId('module_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->boolean('is_completed')->default(false);
                $table->integer('time_spent_seconds')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'lesson_id']);
                $table->index(['user_id', 'module_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_completions');
        Schema::dropIfExists('module_completions');

        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'is_sequential')) {
                $table->dropColumn('is_sequential');
            }
            if (Schema::hasColumn('lessons', 'attachment_title')) {
                $table->dropColumn('attachment_title');
            }
            if (Schema::hasColumn('lessons', 'has_attachment')) {
                $table->dropColumn('has_attachment');
            }
            if (Schema::hasColumn('lessons', 'video_duration')) {
                $table->dropColumn('video_duration');
            }
            if (Schema::hasColumn('lessons', 'content_html')) {
                $table->dropColumn('content_html');
            }
            if (Schema::hasColumn('lessons', 'content_text')) {
                $table->dropColumn('content_text');
            }
            if (Schema::hasColumn('lessons', 'lesson_type')) {
                $table->dropColumn('lesson_type');
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'quiz_attempts_allowed')) {
                $table->dropColumn('quiz_attempts_allowed');
            }
            if (Schema::hasColumn('modules', 'quiz_passing_score')) {
                $table->dropColumn('quiz_passing_score');
            }
            if (Schema::hasColumn('modules', 'order_in_course')) {
                $table->dropColumn('order_in_course');
            }
            if (Schema::hasColumn('modules', 'document_title')) {
                $table->dropColumn('document_title');
            }
            if (Schema::hasColumn('modules', 'has_document')) {
                $table->dropColumn('has_document');
            }
            if (Schema::hasColumn('modules', 'objectives')) {
                $table->dropColumn('objectives');
            }
            if (Schema::hasColumn('modules', 'text_content')) {
                $table->dropColumn('text_content');
            }
            if (Schema::hasColumn('modules', 'intro_video_title')) {
                $table->dropColumn('intro_video_title');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'certificate_template_id')) {
                $table->dropColumn('certificate_template_id');
            }
            if (Schema::hasColumn('courses', 'intro_video_title')) {
                $table->dropColumn('intro_video_title');
            }
            if (Schema::hasColumn('courses', 'intro_video_url')) {
                $table->dropColumn('intro_video_url');
            }
        });
    }
};
