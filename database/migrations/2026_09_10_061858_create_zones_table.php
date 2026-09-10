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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('nomenclature', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('number')->nullable();
            $table->timestamps();
            
            $table->index('region_id');
            $table->index('nomenclature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};