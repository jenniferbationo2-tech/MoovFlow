<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ──── Étendre l'ENUM des statuts ────
        DB::statement("ALTER TABLE inscriptions MODIFY COLUMN statut ENUM(
            'preinscrit',
            'preselectionne',
            'dossier_soumis',
            'en_analyse',
            'recommandee',
            'acceptee',
            'refusee',
            'confirmee',
            'present',
            'absent',
            'annulee',
            'en_attente'
        ) NOT NULL DEFAULT 'preinscrit'");

        Schema::table('inscriptions', function (Blueprint $table): void {

            // ──── Niveau d'inscription ────
            if (!Schema::hasColumn('inscriptions', 'niveau_inscription')) {
                $table->enum('niveau_inscription', ['niveau_1', 'niveau_2'])
                      ->default('niveau_1')
                      ->after('statut');
            }

            // ──── Tracking présélection (Niveau 1 → Niveau 2) ────
            if (!Schema::hasColumn('inscriptions', 'presele_par_id')) {
                $table->unsignedBigInteger('presele_par_id')->nullable();
                $table->foreign('presele_par_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('inscriptions', 'presele_le')) {
                $table->timestamp('presele_le')->nullable();
            }

            // ──── Tracking recommandation organisateur ────
            if (!Schema::hasColumn('inscriptions', 'recommande_par_id')) {
                $table->unsignedBigInteger('recommande_par_id')->nullable();
                $table->foreign('recommande_par_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('inscriptions', 'recommande_le')) {
                $table->timestamp('recommande_le')->nullable();
            }
            if (!Schema::hasColumn('inscriptions', 'note_organisateur')) {
                $table->text('note_organisateur')->nullable();
            }

            // ──── Tracking validation finale (Responsable) ────
            if (!Schema::hasColumn('inscriptions', 'valide_par_id')) {
                $table->unsignedBigInteger('valide_par_id')->nullable();
                $table->foreign('valide_par_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('inscriptions', 'valide_le')) {
                $table->timestamp('valide_le')->nullable();
            }

            // ──── Tracking refus ────
            if (!Schema::hasColumn('inscriptions', 'motif_refus')) {
                $table->text('motif_refus')->nullable();
            }

            // ──── Token sécurisé pour accès au dossier complet ────
            if (!Schema::hasColumn('inscriptions', 'token_acces')) {
                $table->string('token_acces', 64)->unique()->nullable();
            }
            if (!Schema::hasColumn('inscriptions', 'token_expire_at')) {
                $table->timestamp('token_expire_at')->nullable();
            }

            // ──── Check-in (présence) ────
            if (!Schema::hasColumn('inscriptions', 'check_in_at')) {
                $table->timestamp('check_in_at')->nullable();
            }
            if (!Schema::hasColumn('inscriptions', 'check_in_par_id')) {
                $table->unsignedBigInteger('check_in_par_id')->nullable();
                $table->foreign('check_in_par_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->dropForeign(['presele_par_id']);
            $table->dropForeign(['recommande_par_id']);
            $table->dropForeign(['valide_par_id']);
            $table->dropForeign(['check_in_par_id']);

            $table->dropColumn([
                'niveau_inscription',
                'presele_par_id', 'presele_le',
                'recommande_par_id', 'recommande_le', 'note_organisateur',
                'valide_par_id', 'valide_le',
                'motif_refus',
                'token_acces', 'token_expire_at',
                'check_in_at', 'check_in_par_id',
            ]);
        });
    }
};