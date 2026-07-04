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
    Schema::create('learning_paths', function (Blueprint $table) {
        $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->string('badge_url')->nullable();
                $table->string('status')->default('Draft');
                $table->decimal('price', 15, 2)->default(0);
                $table->foreignId('category_id')->constrained();
                $table->string('level')->default('Beginner');
                $table->foreignId('created_by')->constrained('users');
                $table->json('settings')->nullable();
                $table->integer('total_duration_minutes')->default(0);
                $table->integer('courses_count_cache')->default(0);
                $table->json('seo_metadata')->nullable();
                $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_paths');
    }
};
