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
    Schema::create('support_tickets', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number')->unique();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('ticket_category_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->string('subject');
        $table->text('description');
        $table->string('status')->default('open'); // open, pending, resolved, closed
        $table->string('priority')->default('medium'); // low, medium, high
        $table->softDeletes();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
