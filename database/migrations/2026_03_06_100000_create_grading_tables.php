<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Assignments Table
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('max_points', 8, 2)->default(100);
            $table->timestamp('due_at')->nullable();
            $table->string('type')->default('essay'); // essay, project, coding
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // 2. Submissions Table (The missing one)
        Schema::create('submissions', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_graded')->default(false);
            $table->string('status')->default('submitted'); // submitted, graded, resubmitted
            $table->text('content')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback_text')->nullable();
            $table->json('rubric_results')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('grading_duration')->nullable(); // in seconds
            $table->text('internal_notes')->nullable();
            $table->boolean('plagiarism_verified')->default(false);
            $table->timestamps();
        });

        // 3. Rubrics Table
        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria'); // [{ "description": "Clarity", "points": 10, "levels": [...] }]
            $table->timestamps();
        });

        // 4. Grade Revisions/Appeals Table
        Schema::create('grade_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->text('student_reason');
            $table->string('status')->default('pending'); // pending, reviewed, approved, rejected
            $table->decimal('original_grade', 8, 2);
            $table->foreignId('original_instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('arbitrator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('arbitrator_feedback')->nullable();
            $table->decimal('new_grade', 8, 2)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_revisions');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
    }
};