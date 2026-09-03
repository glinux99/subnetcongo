<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étend l'enum enrollments.status pour supporter le workflow complet
        // (pending, active, suspended, rejected, completed, cancelled, expired)
        if (Schema::hasTable('enrollments')) {
            $col = collect(DB::select("SHOW COLUMNS FROM enrollments WHERE Field='status'"))->first();
            if ($col && $col->Type) {
                // Extraire les valeurs actuelles de l'enum
                preg_match("/^enum\((.*)\)$/i", $col->Type, $m);
                $existing = [];
                if (! empty($m[1])) {
                    foreach (explode(',', $m[1]) as $v) {
                        $existing[] = trim($v, "' ");
                    }
                }
                $wanted = ['pending', 'active', 'suspended', 'rejected', 'completed', 'cancelled', 'expired', 'dropped'];
                $merged = array_unique(array_merge($existing, $wanted));
                $list = implode(',', array_map(fn ($v) => "'" . $v . "'", $merged));
                DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM($list) NOT NULL DEFAULT 'pending'");
            }
        }
    }

    public function down(): void
    {
        // Restaurer l'enum d'origine
        if (Schema::hasTable('enrollments')) {
            DB::statement("ALTER TABLE enrollments MODIFY COLUMN status ENUM('active','completed','dropped') NOT NULL DEFAULT 'active'");
        }
    }
};
