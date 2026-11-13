<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rappels automatiques de session live :
 *  - remind_before_minutes : nombre de minutes avant le début pour envoyer le rappel
 *    (NULL = pas de rappel). Ex. 15, 30, 60, 1440.
 *  - reminder_sent_at       : date d'envoi du rappel (évite les doublons).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('live_sessions', 'remind_before_minutes')) {
                $table->unsignedInteger('remind_before_minutes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('live_sessions', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('remind_before_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropColumn(['remind_before_minutes', 'reminder_sent_at']);
        });
    }
};
