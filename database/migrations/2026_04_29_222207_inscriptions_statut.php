<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  
    public function up(): void
    {
        // 1. Migrer les anciens statuts vers les nouveaux
        DB::table('inscriptions')->where('statut', 'attente')->update(['statut' => 'en_attente']);
        DB::table('inscriptions')->where('statut', 'valide')->update(['statut' => 'confirmee']);
        DB::table('inscriptions')->where('statut', 'annule')->update(['statut' => 'annulee']);

        // 2. Modifier l'enum
        Schema::table('inscriptions', function (Blueprint $table): void {
            // Drop puis recrée la colonne pour changer l'enum
            $table->dropColumn('statut');
        });

        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->enum('statut', [
                'en_attente',     // Dossier soumis, en attente d'analyse
                'en_analyse',     // Organisateur en cours d'analyse
                'acceptee',       // Dossier accepté
                'confirmee',      // Confirmée + QR généré
                'refusee',        // Dossier refusé
                'annulee',        // Annulée par participant
                'liste_attente',  // Liste d'attente (quota atteint)
                'present',        // Présent le jour J (post check-in)
            ])->default('en_attente')->after('tarif_id');

            // Champs additionnels du workflow
            $table->text('motif_refus')->nullable()->after('statut');
            $table->dateTime('date_analyse')->nullable()->after('motif_refus');
            $table->foreignId('analyse_par')
                  ->nullable()
                  ->after('date_analyse')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->dropForeign(['analyse_par']);
            $table->dropColumn(['motif_refus', 'date_analyse', 'analyse_par']);
            $table->dropColumn('statut');
        });

        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->enum('statut', ['attente', 'valide', 'annule'])
                  ->default('attente')
                  ->after('tarif_id');
        });
    }
};