<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── contact_messages (formulaire site public) ────────────
        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $t) {
                $t->id();
                $t->string('ticket_number')->unique();
                $t->string('name');
                $t->string('email');
                $t->string('phone', 50)->nullable();
                $t->string('subject', 255);
                $t->longText('message');
                $t->string('status', 20)->default('new'); // new, read, replied, archived, spam
                $t->string('ip_address', 64)->nullable();
                $t->string('user_agent', 500)->nullable();
                $t->string('source', 50)->default('web_contact_form');
                $t->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('replied_at')->nullable();
                $t->longText('reply_message')->nullable();
                $t->timestamps();
                $t->index('status');
                $t->index('email');
            });
        }

        // ── legal_info : identifiant RCCM/IFU/Tax ID ─────────────
        if (! Schema::hasTable('legal_info')) {
            Schema::create('legal_info', function (Blueprint $t) {
                $t->id();
                $t->string('company_name');
                $t->string('legal_form', 100)->nullable(); // SARL, SA, SAS...
                $t->string('rccm', 100)->nullable();
                $t->string('ifu', 50)->nullable();          // Identifiant Fiscal Unique
                $t->string('tax_id', 100)->nullable();
                $t->string('nif', 50)->nullable();
                $t->string('ai', 50)->nullable();           // Article d'Imposition
                $t->string('country', 100)->nullable();
                $t->string('province', 100)->nullable();
                $t->string('city', 100)->nullable();
                $t->string('address_line', 500)->nullable();
                $t->string('po_box', 50)->nullable();
                $t->string('website', 255)->nullable();
                $t->string('capital_social', 100)->nullable();
                $t->string('logo_url')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_info');
        Schema::dropIfExists('contact_messages');
    }
};
