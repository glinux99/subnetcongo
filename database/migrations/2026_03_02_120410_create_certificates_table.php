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
    Schema::create('certificates', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique()->nullable();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('learning_path_id')->nullable()->constrained()->onDelete('cascade');
        $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->onDelete('set null');
        $table->string('certificate_code')->unique();
        $table->decimal('score', 5, 2)->nullable();
        $table->string('status')->default('Active');
        $table->timestamp('issued_at');
        $table->timestamp('expires_at')->nullable();
        $table->timestamp('revoked_at')->nullable();
        $table->foreignId('revoked_by')->nullable()->constrained('users');
        $table->text('revocation_reason')->nullable();
        $table->string('verification_hash')->nullable()->index();
        $table->json('metadata')->nullable();
        $table->string('file_path')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
