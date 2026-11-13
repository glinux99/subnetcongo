<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions de terminal persistantes.
 *
 * Chaque session = un processus `php artisan <command>` détaché côté serveur,
 * avec journal de sortie sous storage/app/terminal/.
 * Le frontend rapatrie le journal via des endpoints de polling (tail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('command', 500);
            $table->text('full_command')->nullable();
            $table->string('title', 150)->nullable();
            $table->string('status', 50)->default('running')->index();
            $table->integer('exit_code')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->text('log_path')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_sessions');
    }
};
