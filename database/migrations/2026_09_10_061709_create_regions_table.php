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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('designation');
            $table->string('code', 50)->unique()->nullable();
            $table->string('type_centrale')->default('solaire'); // solaire, eolien, hydro, thermique, hybride
            $table->integer('puissance_centrale')->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive, maintenance
            $table->timestamps();
            
            $table->index('type_centrale');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};