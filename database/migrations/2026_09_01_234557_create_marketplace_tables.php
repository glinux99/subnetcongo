<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wishlist / Favoris utilisateur
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        // Panier persistant
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['session_id', 'user_id']);
        });

        // Enrichir orders (aligné sur le schéma existant amount/tax_amount/discount_amount)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_provider', 50)->nullable()->after('payment_method');
            $table->string('payment_reference', 200)->nullable()->after('transaction_id');
            $table->string('phone_number', 30)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_name')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
        });

        // Paramètres passerelles de paiement
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique();
            $table->boolean('enabled')->default(false);
            $table->boolean('sandbox')->default(true);
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Avis marketplace
        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
            $table->index(['course_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('payment_gateway_settings');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('wishlists');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_provider', 'payment_reference', 'phone_number', 'country_code',
                'coupon_code', 'billing_email', 'billing_name', 'paid_at', 'refunded_at',
            ]);
        });
    }
};