<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_info', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_info', 'tva_rate')) {
                $table->decimal('tva_rate', 8, 2)->nullable()->default(16.00)->after('ai');
            }
            if (! Schema::hasColumn('legal_info', 'vat_number')) {
                $table->string('vat_number', 50)->nullable()->after('tva_rate');
            }
            if (! Schema::hasColumn('legal_info', 'currency')) {
                $table->string('currency', 10)->nullable()->default('CDF')->after('vat_number');
            }
            // po_box existe déjà dans la table (migration 2026_09_03_090000) — non ajouté ici.
        });
    }

    public function down(): void
    {
        Schema::table('legal_info', function (Blueprint $table) {
            foreach (['tva_rate', 'vat_number', 'currency'] as $col) {
                if (Schema::hasColumn('legal_info', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
