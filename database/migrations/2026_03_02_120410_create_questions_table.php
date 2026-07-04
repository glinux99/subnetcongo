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
    Schema::create('questions', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique()->nullable();
        $table->foreignId('exam_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
        $table->text('question_text');
        $table->string('type')->default('mcq_single');
        $table->string('image_path')->nullable();
        $table->json('options')->nullable(); // Stocker les choix A, B, C, D
        $table->text('correct_answer')->nullable();
        $table->text('explanation')->nullable();
        $table->decimal('points', 8, 2)->default(1.00);
        $table->string('difficulty')->default('intermediate');
        $table->string('status')->default('draft');
        $table->json('metadata')->nullable();
        $table->boolean('is_bank')->default(false);
        $table->foreignId('parent_id')->nullable()->constrained('questions')->onDelete('cascade');
        $table->integer('order')->default(0);
        $table->decimal('success_rate', 5, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
