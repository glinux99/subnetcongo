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
    Schema::create('affiliates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('referral_code')->unique();
        $table->decimal('commission_rate', 5, 2)->default(10.00); // 10%
        $table->decimal('total_earnings', 10, 2)->default(0.00);
        $table->decimal('unpaid_earnings', 10, 2)->default(0.00);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
