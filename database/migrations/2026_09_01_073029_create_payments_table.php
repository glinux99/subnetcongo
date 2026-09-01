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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Propriétés du paiement
            $table->decimal('amount', 10, 2);
            $table->dateTime('payment_date');
            $table->string('payment_method');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->string('category')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            // Relation polymorphe (crée payable_type et payable_id)
            $table->nullableMorphs('payable');

            // Clé étrangère vers la table users pour 'paid_by'
            $table->foreignId('paid_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};