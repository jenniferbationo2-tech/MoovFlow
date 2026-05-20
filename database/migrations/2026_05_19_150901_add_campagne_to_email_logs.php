<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Ajouter campagne_id nullable (un envoi peut être lié à une inscription OU une campagne)
            if (!Schema::hasColumn('email_logs', 'campagne_id')) {
                $table->unsignedBigInteger('campagne_id')->nullable()->after('inscription_id');
                $table->index('campagne_id');
                $table->foreign('campagne_id')
                      ->references('id')
                      ->on('communication_campaigns')
                      ->onDelete('cascade');
            }

            // Rendre inscription_id nullable aussi (au cas où l'envoi est uniquement lié à une campagne)
            if (Schema::hasColumn('email_logs', 'inscription_id')) {
                $table->unsignedBigInteger('inscription_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'campagne_id')) {
                $table->dropForeign(['campagne_id']);
                $table->dropIndex(['campagne_id']);
                $table->dropColumn('campagne_id');
            }
        });
    }
};