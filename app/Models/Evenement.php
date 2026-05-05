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
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'budget_prev' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    /**
     * Configure la journalisation d activite.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('evenement')
            ->logOnly(['titre', 'type_evenement_id', 'date_debut', 'date_fin', 'lieu_id', 'statut', 'budget_prev', 'created_by'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName): string => "Evenement {$eventName}");
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
    // ── NOUVELLES RELATIONS ──────────────────

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



    /**
     * Vérifie si l'événement a des prix à attribuer.
     */
    public function aPrix(): bool
    {
        return in_array($this->typeEvenement?->code, [
            'BARA_MOUSSO',  // Concours
            'SPORT',        // Tournoi sportif
            'HACK',         // Hackathon
            'CHALLENGE',    // Challenge innovation
        ]);
    }

    /**
     * Vérifie si l'événement est compétitif.
     */
    public function estCompetitif(): bool
    {
        return $this->aPrix();
    }

    /**
     * Vérifie si c'est un salon (Moov participe à un événement externe).
     */
    public function estSalon(): bool
    {
        return $this->typeEvenement?->code === 'SALON';
    }


}
