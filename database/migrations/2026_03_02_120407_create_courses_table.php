<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable(); // Chemin relatif vers l'image de couverture
            $table->string('preview_video_url')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('promo_price', 10, 2)->nullable(); // Prix promotionnel
            $table->string('level')->default('all');
            $table->integer('estimated_duration')->default(0);
            $table->string('language')->default('fr');
            // Statuts : draft, pending, published, archived
            $table->string('status')->default('draft');
            $table->boolean('certificate_enabled')->default(true);
            $table->boolean('requires_enrollment')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->text('short_info')->nullable(); // Description courte
            $table->text('prerequisites')->nullable();
            $table->json('tags')->nullable();        // Mots-clés
            $table->json('seo_metadata')->nullable(); // { title, description, keywords }
            // Clés étrangères
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table ->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};