<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes utiles a l administration des comptes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('nom')->nullable()->after('name');
            $table->string('prenom')->nullable()->after('nom');
            $table->boolean('is_active')->default(true)->after('telephone');
        });
    }

    /**
     * Supprime les colonnes ajoutees.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['nom', 'prenom', 'is_active']);
        });
    }
};