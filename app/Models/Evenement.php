<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Evenement extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'titre',
        'description',
        'visuel',
        'reglement_pdf',
        'type_evenement_id',
        'date_debut',
        'date_fin',
        'lieu_id',
        'statut',
        'budget_prev',
        'created_by',
        //  Champs Salon
        'nom_salon_hote',
        'organisateur_externe',
        'lieu_stand',
        'superficie_stand',
        'objectifs_stand',
        'objectif_prospects',
        'date_demande_validation',
        'date_publication',
        'validated_by',
        'motif_rejet',
        'public_cible',
        'cible_beneficiaires',
        'objectifs_principaux',
        'criteres_candidature',
        'domaines_acceptes',
        'dotation_principale',
        'nombre_laureates',
        'age_min',
        'age_max',
        'theme_principal',
        'profession_cible',
        'programme_agenda',
        'diffusion_en_ligne',
        'lien_zoom',
        'document_joint',
        'discipline',
        'categorie_age',
        'nombre_max_equipes',
        'effectif_min',
        'effectif_max',
        'format_competition',
        'trophees_prix',
        'thematique_challenge',
        'criteres_evaluation',
        'stades_acceptes',
        'dotation_totale',
        'date_cloture_dossiers',
        'domaine_formation',
        'niveau_requis',
        'duree_heures',
        'certification',
        'nom_certification',
        'programme_detaille',
        'materiel_requis',
        'theme_hackathon',
        'duree_heures_hack',
        'equipe_min',
        'equipe_max',
        'technologies_suggerees',
        'criteres_evaluation_hack',
        'nom_salon_hote',
        'organisateur_externe',
        'lieu_stand',
        'superficie_stand',
        'objectifs_stand',
        'objectif_prospects',

    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'budget_prev' => 'decimal:2',
        'deleted_at' => 'datetime',
        'date_demande_validation' => 'datetime',
        'date_publication'        => 'datetime',

        'domaines_acceptes'      => 'array',
        'stades_acceptes'        => 'array',
        'technologies_suggerees' => 'array',

        'diffusion_en_ligne' => 'boolean',
        'certification'      => 'boolean',

        'date_cloture_dossiers' => 'date',
        'dotation_principale' => 'decimal:2',
        'dotation_totale'     => 'decimal:2',
    ];
    protected $appends = ['visuel_url', 'reglement_pdf_url'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('evenement')
            ->logOnly(['titre', 'type_evenement_id', 'date_debut', 'date_fin', 'lieu_id', 'statut', 'budget_prev', 'created_by'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName): string => "Evenement {$eventName}");
    }
    public function getVisuelUrlAttribute(): ?string
    {
        return $this->visuel ? asset('storage/' . $this->visuel) : null;
    }

    public function getReglementPdfUrlAttribute(): ?string
    {
        return $this->reglement_pdf ? asset('storage/' . $this->reglement_pdf) : null;
    }

    public function typeEvenement(): BelongsTo
    {
        return $this->belongsTo(TypeEvenement::class, 'type_evenement_id');
    }

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function tarifs(): HasMany
    {
        return $this->hasMany(Tarif::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function objectifsRse(): HasMany
    {
        return $this->hasMany(ObjectifRse::class);
    }

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(Equipe::class);
    }

    public function ressources(): HasMany
    {
        return $this->hasMany(Ressource::class);
    }

    public function dotations(): HasMany
    {
        return $this->hasMany(Dotation::class);
    }

    public function enquetes(): HasMany
    {
        return $this->hasMany(Enquete::class);
    }

    public function benevolesAffectations(): HasMany
    {
        return $this->hasMany(BenevoleAffectation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function communicationCampaigns(): HasMany
    {
        return $this->hasMany(CommunicationCampaign::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function b2bMeetings(): HasMany
    {
        return $this->hasMany(B2BMeeting::class);
    }


    public function dossiers(): HasMany
    {
        return $this->hasMany(\App\Models\DossierInscription::class)
            ->whereHas('inscription', fn($q) => $q->where('evenement_id', $this->id));
    }

    public function prix(): HasMany
    {
        return $this->hasMany(\App\Models\Prix::class)->orderBy('rang');
    }

    public function competitionPhases(): HasMany
    {
        return $this->hasMany(\App\Models\CompetitionPhase::class)->orderBy('ordre');
    }

    public function benevoles(): HasMany
    {
        return $this->hasMany(\App\Models\Benevole::class);
    }

    public function intervenants(): HasMany
    {
        return $this->hasMany(\App\Models\Intervenant::class);
    }


    public function aPrix(): bool
    {
        return in_array($this->typeEvenement?->code, [
            'BARA_MOUSSO',  // Concours
            'SPORT',        // Tournoi sportif
            'HACK',         // Hackathon
            'CHALLENGE',    // Challenge innovation
        ]);
    }

    public function estCompetitif(): bool
    {
        return $this->aPrix();
    }

    public function estSalon(): bool
    {
        return $this->typeEvenement?->code === 'SALON';
    }

    /**
     * Matériel affecté à cet événement.
     */
    public function materiels(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Materiel::class, 'evenement_materiel')
                    ->withPivot(['quantite_prevue', 'quantite_sortie', 'quantite_retournee', 'statut', 'note'])
                    ->withTimestamps();
    }

    /**
     * Prestataires affectés à cet événement.
     */
    public function prestataires(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Prestataire::class, 'evenement_prestataire')
                    ->withPivot(['prestation', 'montant_prevu', 'montant_final', 'statut', 'contrat_pdf', 'note'])
                    ->withTimestamps();
    }

    /**
     * Postes bénévoles ouverts pour cet événement.
     */
    public function postesBenevoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PosteBenevole::class);
    }
}
