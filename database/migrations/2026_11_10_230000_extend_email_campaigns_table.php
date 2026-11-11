<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('email_campaigns', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('email_campaigns', 'from_name')) {
                $table->string('from_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('email_campaigns', 'from_email')) {
                $table->string('from_email')->nullable()->after('from_name');
            }
            if (! Schema::hasColumn('email_campaigns', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('email_campaigns', 'total_recipients')) {
                $table->unsignedInteger('total_recipients')->default(0)->after('user_id');
            }
            if (! Schema::hasColumn('email_campaigns', 'sent_count')) {
                $table->unsignedInteger('sent_count')->default(0)->after('total_recipients');
            }
            if (! Schema::hasColumn('email_campaigns', 'opens_count')) {
                $table->unsignedInteger('opens_count')->default(0)->after('sent_count');
            }
            if (! Schema::hasColumn('email_campaigns', 'clicks_count')) {
                $table->unsignedInteger('clicks_count')->default(0)->after('opens_count');
            }
            if (! Schema::hasColumn('email_campaigns', 'bounces_count')) {
                $table->unsignedInteger('bounces_count')->default(0)->after('clicks_count');
            }
            if (! Schema::hasColumn('email_campaigns', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            }
        });

        // Backfill avant passage en NOT NULL / default
        DB::table('email_campaigns')->whereNull('target_audience')->update(['target_audience' => 'all']);
        DB::table('email_campaigns')->whereNull('status')->update(['status' => 'draft']);

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->string('target_audience')->default('all')->change();
            $table->string('status')->default('draft')->change();
            $table->index('status');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'name',
                'from_name',
                'from_email',
                'user_id',
                'total_recipients',
                'sent_count',
                'opens_count',
                'clicks_count',
                'bounces_count',
                'scheduled_at',
            ]);
        });
    }
};
