<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greeting_messages', function (Blueprint $table) {
            $table->id();
            // Audience ciblée : all | student | instructor | admin | staff
            $table->string('audience', 20)->default('all')->index();
            // Moment de la journée : all | morning | afternoon | evening
            $table->string('period', 20)->default('all');
            $table->string('message', 500);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greeting_messages');
    }
};
