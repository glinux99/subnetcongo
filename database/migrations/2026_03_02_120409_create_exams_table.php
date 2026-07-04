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
    Schema::create('exams', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique()->nullable();
        $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('type')->default('Quiz');
        $table->string('status')->default('Draft');
        $table->decimal('pass_percentage', 5, 2)->default(50.00);
        $table->integer('duration_minutes')->nullable();
        $table->integer('attempts_allowed')->default(0);
        $table->json('settings')->nullable();
        $table->boolean('is_published')->default(false);
        $table->boolean('randomize_questions')->default(false);
        $table->boolean('randomize_options')->default(false);
        $table->boolean('show_correct_answers')->default(true);
        $table->boolean('show_results_immediately')->default(true);
        $table->boolean('require_lockdown_browser')->default(false);
        $table->string('password')->nullable();
        $table->dateTime('available_from')->nullable();
        $table->dateTime('available_until')->nullable();
        $table->integer('time_limit_minutes')->nullable();
        $table->integer('total_points')->default(0);
        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
