<?php
// database/migrations/2025_01_01_000002_create_lessons_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique()->nullable()  ;
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['video', 'document', 'quiz', 'assignment'])->default('video');
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
            $table->string('document_title')->nullable();
            $table->string('document_url')->nullable();
            
            $table->enum('video_source', ['youtube', 'vimeo', 'custom'])->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_free_preview')->default(false);
            $table->string('status')->default('draft');
            $table->integer('order')->default(0);
            $table->json('attachment_paths')->nullable();   // Stocke les URLs des fichiers joints
            $table->json('seo_metadata')->nullable();       // title, description, keywords
            $table->softDeletes();
            $table->timestamps();

            $table->index(['module_id', 'order']);
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};