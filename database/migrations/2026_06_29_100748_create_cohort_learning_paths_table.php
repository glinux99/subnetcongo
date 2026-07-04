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
        Schema::create('cohort_learning_path', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('cohort_id')->constrained()->onDelete('cascade'); // Foreign key to cohorts table
            $table->foreignId('learning_path_id')->constrained()->onDelete('cascade'); // Foreign key to learning_paths table
            $table->unique(['cohort_id', 'learning_path_id']); // Ensure unique combination
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohort_learning_paths');
    }
};
