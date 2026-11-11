<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_audience_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_audience_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('email_audience_groups')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'email']);
        });

        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->foreignId('audience_group_id')
                ->nullable()
                ->after('target_audience')
                ->constrained('email_audience_groups')
                ->nullOnDelete();
            $table->json('extra_emails')->nullable()->after('audience_group_id');
            $table->json('excluded_emails')->nullable()->after('extra_emails');
        });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audience_group_id');
            $table->dropColumn(['extra_emails', 'excluded_emails']);
        });
        Schema::dropIfExists('email_audience_group_members');
        Schema::dropIfExists('email_audience_groups');
    }
};
