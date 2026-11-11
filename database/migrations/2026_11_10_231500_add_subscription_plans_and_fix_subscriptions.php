<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la table subscription_plans et complète la table subscriptions
     * (colonnes manquantes + compatibilité avec l'ancien schéma `name` / stripe_* NOT NULL).
     * Ne supprime rien : additif uniquement.
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('price')->comment('Prix en cents');
                $table->string('currency', 3)->default('USD');
                $table->enum('interval', ['month', 'year'])->default('month');
                $table->unsignedInteger('duration_months')->default(1);
                $table->json('features')->nullable();
                $table->json('course_ids')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'plan_code')) {
                $table->string('plan_code')->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('subscriptions', 'plan_name')) {
                $table->string('plan_name')->nullable()->after('plan_code');
            }
            if (!Schema::hasColumn('subscriptions', 'plan_price')) {
                $table->unsignedInteger('plan_price')->nullable()->after('plan_name')->comment('Prix en cents');
            }
            if (!Schema::hasColumn('subscriptions', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('plan_price');
            }
            if (!Schema::hasColumn('subscriptions', 'interval')) {
                $table->string('interval', 10)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->string('status', 20)->default('active')->after('ends_at')->index();
            }
            if (!Schema::hasColumn('subscriptions', 'total_paid')) {
                $table->unsignedInteger('total_paid')->default(0)->after('status')->comment('Total encaissé, en cents');
            }
        });

        // Compatibilité avec l'ancien schéma (table actuellement vide en local) :
        // - `name` reste NOT NULL -> défaut pour les inserts applicatifs
        // - stripe_id / stripe_status deviennent nullables (pas de sync Stripe pour l'instant)
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('name')->default('default')->change();
            $table->string('stripe_id')->nullable()->change();
            $table->string('stripe_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['plan_code', 'plan_name', 'plan_price', 'currency', 'interval', 'starts_at', 'status', 'total_paid'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('subscription_plans');
    }
};
