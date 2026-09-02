<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // exam_attempts: enrich for resume / proctoring / time tracking
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'time_spent_seconds')) {
                $table->unsignedInteger('time_spent_seconds')->default(0)->after('user_agent');
            }
            if (!Schema::hasColumn('exam_attempts', 'time_left')) {
                $table->unsignedInteger('time_left')->nullable()->after('time_spent_seconds');
            }
            if (!Schema::hasColumn('exam_attempts', 'proctoring_data')) {
                $table->json('proctoring_data')->nullable()->after('time_left');
            }
            if (!Schema::hasColumn('exam_attempts', 'flagged_reason')) {
                $table->string('flagged_reason')->nullable()->after('proctoring_data');
            }
        });

        // live_sessions: enrich for advanced scheduling
        Schema::table('live_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('live_sessions', 'actual_start_at')) {
                $table->timestamp('actual_start_at')->nullable()->after('start_at');
            }
            if (!Schema::hasColumn('live_sessions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('actual_start_at');
            }
            if (!Schema::hasColumn('live_sessions', 'provider_response')) {
                $table->json('provider_response')->nullable();
            }
        });

        // live_attendances: enrich for engagement
        Schema::table('live_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('live_attendances', 'seconds_spent')) {
                $table->unsignedInteger('seconds_spent')->default(0);
            }
            if (!Schema::hasColumn('live_attendances', 'last_ping_at')) {
                $table->timestamp('last_ping_at')->nullable();
            }
        });

        // enrollments: enrich for progress tracking
        if (!Schema::hasColumn('enrollments', 'last_lesson_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('last_lesson_id')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['time_spent_seconds', 'time_left', 'proctoring_data', 'flagged_reason']);
        });
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['actual_start_at', 'ended_at', 'provider_response']);
        });
        Schema::table('live_attendances', function (Blueprint $table) {
            $table->dropColumn(['seconds_spent', 'last_ping_at']);
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['last_lesson_id']);
        });
    }
};
