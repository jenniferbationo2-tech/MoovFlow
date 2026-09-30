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
        'motivation', 'fichier_joint',
        // Bara Mousso
        'localite', 'domaine_activite', 'effectif_employe',
        'annee_creation', 'url_video_pitch', 'besoins_financiers', 'description_projet',
        // Sport
        'nom_equipe', 'capitaine', 'categorie_age', 'effectif_equipe',
        'couleurs_maillot', 'coach_nom', 'joueurs_licencies',
        // Hackathon
        'nom_equipe_hack', 'niveau_equipe', 'technologies',
        'presence_complete', 'url_portfolio', 'idee',
        // Challenge Innovation
        'titre_idee', 'description', 'stade_maturite', 'marche_vise',
        'investissement_requis', 'statut_juridique',
        // Salon / Formation (défaut)
        'organisation', 'fonction',
    ];

    protected $casts = [
        'budget_projet'           => 'decimal:2',
        'nb_membres_association'  => 'integer',
        'besoins_financiers'      => 'decimal:2',
        'investissement_requis'   => 'decimal:2',
        'annee_creation'          => 'integer',
        'effectif_equipe'         => 'integer',
        'joueurs_licencies'       => 'integer',
        'presence_complete'       => 'boolean',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }
}