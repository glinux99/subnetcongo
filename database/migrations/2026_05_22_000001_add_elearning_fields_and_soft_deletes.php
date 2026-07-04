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
        // Update modules table
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(0)->after('order');
            }
            if (!Schema::hasColumn('modules', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('duration_minutes');
            }
            if (!Schema::hasColumn('modules', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update lessons table
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('is_free_preview');
            }
            if (!Schema::hasColumn('lessons', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('is_published');
            }
            if (!Schema::hasColumn('lessons', 'quiz_id')) {
                $table->foreignId('quiz_id')->nullable()->after('attachment_path')->constrained('exams')->onDelete('set null');
            }
            if (!Schema::hasColumn('lessons', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update enrollments table
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'expires_at')) {
                $table->dateTime('expires_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('enrollments', 'certificate_id')) {
                $table->foreignId('certificate_id')->nullable()->after('expires_at')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('enrollments', 'grade')) {
                $table->string('grade')->nullable()->after('certificate_id');
            }
            if (!Schema::hasColumn('enrollments', 'notes')) {
                $table->text('notes')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('enrollments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update user_progress table
        Schema::table('user_progress', function (Blueprint $table) {
            if (!Schema::hasColumn('user_progress', 'time_spent_seconds')) {
                $table->integer('time_spent_seconds')->default(0)->after('completed_at');
            }
            if (!Schema::hasColumn('user_progress', 'notes')) {
                $table->text('notes')->nullable()->after('time_spent_seconds');
            }
        });

        // Update exams table
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('status');
            }
            if (!Schema::hasColumn('exams', 'randomize_questions')) {
                $table->boolean('randomize_questions')->default(true)->after('is_published');
            }
            if (!Schema::hasColumn('exams', 'randomize_options')) {
                $table->boolean('randomize_options')->default(true)->after('randomize_questions');
            }
            if (!Schema::hasColumn('exams', 'show_correct_answers')) {
                $table->boolean('show_correct_answers')->default(true)->after('randomize_options');
            }
            if (!Schema::hasColumn('exams', 'show_results_immediately')) {
                $table->boolean('show_results_immediately')->default(true)->after('show_correct_answers');
            }
            if (!Schema::hasColumn('exams', 'require_lockdown_browser')) {
                $table->boolean('require_lockdown_browser')->default(false)->after('show_results_immediately');
            }
            if (!Schema::hasColumn('exams', 'password')) {
                $table->string('password')->nullable()->after('require_lockdown_browser');
            }
            if (!Schema::hasColumn('exams', 'available_from')) {
                $table->dateTime('available_from')->nullable()->after('password');
            }
            if (!Schema::hasColumn('exams', 'available_until')) {
                $table->dateTime('available_until')->nullable()->after('available_from');
            }
            if (!Schema::hasColumn('exams', 'time_limit_minutes')) {
                $table->integer('time_limit_minutes')->nullable()->after('available_until');
            }
            if (!Schema::hasColumn('exams', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update questions table
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'question_text')) {
                $table->text('question_text')->after('uuid');
            }
            if (!Schema::hasColumn('questions', 'correct_answer')) {
                $table->text('correct_answer')->nullable()->after('options');
            }
            if (!Schema::hasColumn('questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('correct_answer');
            }
            if (!Schema::hasColumn('questions', 'points')) {
                $table->decimal('points', 5, 2)->default(1)->after('explanation');
            }
            if (!Schema::hasColumn('questions', 'difficulty')) {
                $table->string('difficulty')->default('medium')->after('points');
            }
            if (!Schema::hasColumn('questions', 'status')) {
                $table->string('status')->default('draft')->after('difficulty');
            }
            if (!Schema::hasColumn('questions', 'is_bank')) {
                $table->boolean('is_bank')->default(false)->after('metadata');
            }
            if (!Schema::hasColumn('questions', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('is_bank')->constrained('questions')->onDelete('set null');
            }
            if (!Schema::hasColumn('questions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update exam_attempts table
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'time_spent_seconds')) {
                $table->integer('time_spent_seconds')->default(0)->after('user_agent');
            }
            if (!Schema::hasColumn('exam_attempts', 'proctoring_data')) {
                $table->json('proctoring_data')->nullable()->after('time_spent_seconds');
            }
            if (!Schema::hasColumn('exam_attempts', 'flagged_reason')) {
                $table->text('flagged_reason')->nullable()->after('proctoring_data');
            }
        });

        // Update exam_results table
        Schema::table('exam_results', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_results', 'passed_at')) {
                $table->dateTime('passed_at')->nullable()->after('is_passed');
            }
            if (!Schema::hasColumn('exam_results', 'feedback')) {
                $table->text('feedback')->nullable()->after('passed_at');
            }
            if (!Schema::hasColumn('exam_results', 'graded_by')) {
                $table->foreignId('graded_by')->nullable()->after('feedback')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('exam_results', 'grade_notes')) {
                $table->text('grade_notes')->nullable()->after('graded_by');
            }
        });

        // Update categories table
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon')->nullable()->after('type');
            }
            if (!Schema::hasColumn('categories', 'color')) {
                $table->string('color')->default('#6366F1')->after('icon');
            }
            if (!Schema::hasColumn('categories', 'order')) {
                $table->integer('order')->default(0)->after('color');
            }
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('order')->constrained('categories')->onDelete('set null');
            }
            if (!Schema::hasColumn('categories', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Update courses table
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(0)->after('certificate_enabled');
            }
            if (!Schema::hasColumn('courses', 'language')) {
                $table->string('language')->default('fr')->after('duration_minutes');
            }
        });

        // Update users table for e-learning
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('numero');
            }
            if (!Schema::hasColumn('users', 'specialization')) {
                $table->string('specialization')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'social_links')) {
                $table->json('social_links')->nullable()->after('specialization');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse changes in reverse order
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'specialization', 'social_links']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'language']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['icon', 'color', 'order', 'parent_id', 'deleted_at']);
        });

        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropForeign(['graded_by']);
            $table->dropColumn(['passed_at', 'feedback', 'graded_by', 'grade_notes']);
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['time_spent_seconds', 'proctoring_data', 'flagged_reason']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['question_text', 'correct_answer', 'explanation', 'points', 'difficulty', 'status', 'is_bank', 'parent_id', 'deleted_at']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'randomize_questions', 'randomize_options', 'show_correct_answers', 'show_results_immediately', 'require_lockdown_browser', 'password', 'available_from', 'available_until', 'time_limit_minutes', 'deleted_at']);
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn(['time_spent_seconds', 'notes']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
            $table->dropColumn(['expires_at', 'certificate_id', 'grade', 'notes', 'deleted_at']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn(['is_published', 'attachment_path', 'quiz_id', 'deleted_at']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'is_published', 'deleted_at']);
        });
    }
};