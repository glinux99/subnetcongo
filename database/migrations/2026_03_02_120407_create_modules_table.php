<?php
// database/migrations/2025_01_01_000001_create_modules_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique()->nullable();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->integer('order')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('estimated_duration')->nullable(); // minutes
            $table->string('thumbnail_path')->nullable();
            $table->json('seo_metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['course_id', 'order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};