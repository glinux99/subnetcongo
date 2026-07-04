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
    Schema::create('forum_topics', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('forum_category_id')->constrained()->onDelete('cascade');
        $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
        $table->string('slug')->unique()->nullable();
        $table->string('title');
        $table->longText('content')->nullable();
        $table->string('attachment_path')->nullable();
        $table->string('attachment_name')->nullable();
        $table->string('status')->default('active'); // active, reported, archived, resolved
        $table->boolean('is_pinned')->default(false);
        $table->boolean('is_locked')->default(false);
        $table->boolean('is_resolved')->default(false);
        $table->integer('views_count')->default(0);
        $table->integer('replies_count')->default(0);
        $table->timestamp('last_post_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_topics');
    }
};
