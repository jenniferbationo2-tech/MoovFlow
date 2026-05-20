<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationCampaign extends Model
{
    use HasFactory;

    protected $table = 'communication_campaigns';

    protected $fillable = [
        'evenement_id',
        'objet',
        'contenu',
        'mode_destinataires',
        'emails_personnalises',
        'statut',
        'date_envoi',
        'nb_destinataires',
        'nb_ouvertures',
        'nb_acceptations',
    ];

    protected $casts = [
        'emails_personnalises' => 'array',
        'date_envoi'           => 'datetime',
    ];

    // ─── RELATIONS ──────────────────────
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function envois(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campagne_id');
    }

    // ─── SCOPES ─────────────────────────
    public function scopeBrouillon(Builder $q): Builder
    {
        return $q->where('statut', 'brouillon');
    }

    public function scopeEnvoyee(Builder $q): Builder
    {
        return $q->where('statut', 'envoyee');
    }

    // ─── HELPERS ────────────────────────
    public function getNbEnvoyesAttribute(): int
    {
        return $this->envois()->where('statut', 'sent')->count();
    }

    public function getNbErreursAttribute(): int
    {
        return $this->envois()->where('statut', 'failed')->count();
    }

    public function getEstEnvoyeeAttribute(): bool
    {
        return $this->statut === 'envoyee';
    }

    public function getEstBrouillonAttribute(): bool
    {
        return $this->statut === 'brouillon';
    }

    /**
     * Labels des cibles disponibles.
     */
    public static function ciblesDisponibles(): array
    {
        return [
            'tous'      => 'Tous les inscrits',
            'valides'   => 'Inscriptions validées',
            'presents'  => 'Participants présents',
            'absents'   => 'Inscrits absents',
            'refuses'   => 'Inscriptions refusées',
        ];
    }

    /**
     * Modèles pré-définis (rappel + remerciement).
     */
    public static function modelesPredefinis(): array
    {
        return [
            'rappel_j1' => [
                'titre'  => 'Rappel J-1',
                'objet'  => 'Demain : {evenement}',
                'contenu' => "Bonjour {prenom},\n\nNous avons hâte de vous accueillir DEMAIN à l'événement :\n\n« {evenement} »\n\n📅 Date : {date_evenement}\n📍 Lieu : {lieu}\n🎫 Référence : {reference}\n\nN'oubliez pas votre QR code de billet (envoyé dans le mail de confirmation).\n\nÀ très bientôt !\n\nL'équipe {app_name}",
            ],
            'remerciement' => [
                'titre'  => 'Remerciement post-événement',
                'objet'  => 'Merci de votre participation à {evenement}',
                'contenu' => "Bonjour {prenom},\n\nMerci d'avoir participé à l'événement « {evenement} » qui s'est tenu le {date_evenement}.\n\nNous espérons que vous avez apprécié ce moment. N'hésitez pas à remplir l'enquête de satisfaction pour nous aider à améliorer nos futurs événements.\n\nVotre certificat de participation est disponible dans votre espace personnel.\n\nÀ bientôt pour de nouvelles aventures !\n\nL'équipe {app_name}",
            ],
        ];
    }
}