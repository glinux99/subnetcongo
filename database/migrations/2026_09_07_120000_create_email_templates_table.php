<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Unique identifier for the template');
            $table->string('name')->comment('Human-readable name');
            $table->string('category')->comment('Category: certificate, enrollment, course, exam, welcome, support, marketing');
            $table->string('trigger_event')->comment('Event that triggers this template');
            $table->string('subject')->comment('Email subject with placeholders');
            $table->longText('content')->comment('Email HTML content with placeholders');
            $table->json('variables')->nullable()->comment('List of available variables');
            $table->boolean('is_active')->default(true)->comment('Whether the template is active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
