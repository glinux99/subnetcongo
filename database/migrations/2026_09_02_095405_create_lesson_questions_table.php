<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('lesson_questions')->cascadeOnDelete();
            $table->string('subject', 255);
            $table->text('body');
            $table->enum('status', ['open', 'answered', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('upvotes')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_questions');
    }
};
