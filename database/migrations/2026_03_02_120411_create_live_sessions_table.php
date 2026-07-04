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
    Schema::create('live_sessions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('course_id')->constrained()->onDelete('cascade');
        $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
        $table->string('title');
        $table->dateTime('start_at')->nullable();
        $table->integer('duration')->default(60);
        $table->string('provider')->default('zoom');
        $table->integer('max_participants')->default(100);
        $table->boolean('is_recorded')->default(false);
        $table->string('passcode')->nullable();
        $table->json('settings')->nullable();
        $table->string('meeting_id')->nullable();
        $table->string('meeting_url')->nullable(); // Lien Zoom/Meet
        $table->string('host_url')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->json('metadata')->nullable();
        $table->foreignId('cohort_id')->nullable()->constrained()->onDelete('cascade');
        $table->string('status')->default('scheduled'); // scheduled, live, finished
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
