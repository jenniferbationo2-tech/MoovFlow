<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Intervenants : gérés par l'organisateur, PAS de compte plateforme.
     * (Conférenciers, formateurs, mentors).
     */
    public function up(): void
    {
        Schema::create('intervenants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evenement_id')
                  ->constrained('evenements')
                  ->cascadeOnDelete();

            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            $table->text('biographie')->nullable();
            $table->string('photo')->nullable();
            $table->string('specialite')->nullable();
            $table->text('disponibilites')->nullable();

            // Notes de frais et cachets (perdiems)
            $table->decimal('taux_cachet', 15, 2)->default(0);
            $table->decimal('montant_perdiems', 15, 2)->default(0);

            $table->enum('statut_paiement', [
                'non_paye', 'paye'
            ])->default('non_paye');

            $table->timestamps();

            $table->index('evenement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenants');
    }
};