<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Champs pour le workflow d'approbation admin
            if (! Schema::hasColumn('enrollments', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('course_id');
            }
            if (! Schema::hasColumn('enrollments', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('enrolled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('enrollments', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('enrollments', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('enrollments', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('admin_notes');
            }
            if (! Schema::hasColumn('enrollments', 'revoked_reason')) {
                $table->string('revoked_reason', 255)->nullable()->after('revoked_at');
            }
            if (! Schema::hasColumn('enrollments', 'last_lesson_id')) {
                $table->foreignId('last_lesson_id')->nullable()->after('revoked_reason')->constrained('lessons')->nullOnDelete();
            }

            $table->index('order_id');
            $table->index('reviewed_by');
            $table->index(['status', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            foreach (['order_id', 'reviewed_by', 'reviewed_at', 'admin_notes', 'revoked_at', 'revoked_reason', 'last_lesson_id'] as $col) {
                if (Schema::hasColumn('enrollments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
