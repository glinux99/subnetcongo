<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // Adresses email recevant une copie (BCC) à chaque envoi de ce template.
            // Tableau JSON de strings, ex: ["admin@exemple.com","copie@exemple.com"]
            $table->json('bcc_emails')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('bcc_emails');
        });
    }
};
