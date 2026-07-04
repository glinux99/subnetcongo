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
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('live_session_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('video_url');
            $table->string('provider')->default('custom')->comment('zoom, vimeo, youtube, s3');
            $table->string('external_id')->nullable()->comment('ID de la vidéo chez le provider');
            $table->integer('duration_seconds')->default(0);
            $table->bigInteger('file_size')->nullable()->comment('Taille en octets');
            $table->string('status')->default('available')->comment('processing, available, error');
            $table->boolean('is_public')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
