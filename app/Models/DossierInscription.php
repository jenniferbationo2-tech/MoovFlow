<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierInscription extends Model
{
    use HasFactory;

    protected $table = 'dossiers_inscription';

    protected $fillable = [
        'inscription_id',
        // Commun
        'organisation', 'fonction', 'motivation', 'fichier_joint',
        // Bara Mousso
        'nom_association', 'description_projet',
        'nb_membres_association', 'budget_projet',
        // Football
        'nom_equipe', 'nb_joueurs',
        'categorie_equipe', 'responsable_equipe',
        // Hackathon
        'competences_techniques', 'stack_technologique',
        'nom_equipe_hack', 'nb_membres_equipe',
        // Formation
        'niveau_formation', 'objectifs_apprentissage',
        // Salon
        'secteur_activite', 'type_visite_salon', 'interets_b2b',
        // Challenge Innovation
        'titre_idee', 'secteur_idee', 'fichier_presentation',
    ];

    protected $casts = [
        'budget_projet' => 'decimal:2',
        'nb_membres_association' => 'integer',
        'nb_joueurs' => 'integer',
        'nb_membres_equipe' => 'integer',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }
}