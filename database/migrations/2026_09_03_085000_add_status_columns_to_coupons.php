<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coupons')) {
            Schema::table('coupons', function (Blueprint $t) {
                if (! Schema::hasColumn('coupons', 'status')) {
                    $t->string('status', 20)->default('active')->after('used_count');
                }
                if (! Schema::hasColumn('coupons', 'is_active')) {
                    $t->boolean('is_active')->default(true)->after('status');
                }
                if (! Schema::hasColumn('coupons', 'description')) {
                    $t->text('description')->nullable()->after('code');
                }
                if (! Schema::hasColumn('coupons', 'starts_at')) {
                    $t->timestamp('starts_at')->nullable()->after('expires_at');
                }
                if (! Schema::hasColumn('coupons', 'min_amount')) {
                    $t->decimal('min_amount', 10, 2)->nullable()->after('discount_value');
                }
                if (! Schema::hasColumn('coupons', 'max_uses_per_user')) {
                    $t->unsignedInteger('max_uses_per_user')->nullable()->after('usage_limit');
                }
                $t->index('status');
                $t->index('is_active');
            });
        }
    }

    public function down(): void
    {
        // no-op (columns conservées)
    }
};
