<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Courses: final exam, certificate settings, scoring
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'has_final_exam')) {
                $table->boolean('has_final_exam')->default(false)->after('status');
            }
            if (!Schema::hasColumn('courses', 'final_exam_id')) {
                $table->unsignedBigInteger('final_exam_id')->nullable()->after('has_final_exam');
            }
            if (!Schema::hasColumn('courses', 'final_exam_weight')) {
                $table->decimal('final_exam_weight', 5, 2)->default(20)->after('final_exam_id');
            }
            if (!Schema::hasColumn('courses', 'passing_score_20')) {
                $table->decimal('passing_score_20', 5, 2)->default(10)->after('final_exam_weight');
            }
            if (!Schema::hasColumn('courses', 'quiz_weight_total')) {
                $table->decimal('quiz_weight_total', 5, 2)->default(80)->after('passing_score_20');
            }
        });

        // Exams: link to module
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'module_id')) {
                $table->unsignedBigInteger('module_id')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('exams', 'is_module_quiz')) {
                $table->boolean('is_module_quiz')->default(false)->after('module_id');
            }
            if (!Schema::hasColumn('exams', 'is_final_exam')) {
                $table->boolean('is_final_exam')->default(false)->after('is_module_quiz');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'quiz_weight_total')) {
                $table->dropColumn('quiz_weight_total');
            }
            if (Schema::hasColumn('courses', 'passing_score_20')) {
                $table->dropColumn('passing_score_20');
            }
            if (Schema::hasColumn('courses', 'final_exam_weight')) {
                $table->dropColumn('final_exam_weight');
            }
            if (Schema::hasColumn('courses', 'final_exam_id')) {
                $table->dropColumn('final_exam_id');
            }
            if (Schema::hasColumn('courses', 'has_final_exam')) {
                $table->dropColumn('has_final_exam');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'is_final_exam')) {
                $table->dropColumn('is_final_exam');
            }
            if (Schema::hasColumn('exams', 'is_module_quiz')) {
                $table->dropColumn('is_module_quiz');
            }
            if (Schema::hasColumn('exams', 'module_id')) {
                $table->dropColumn('module_id');
            }
        });
    }
};
