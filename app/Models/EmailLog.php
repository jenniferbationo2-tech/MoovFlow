<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_id',
        'type',
        'destinataire',
        'sujet',
        'contenu',
        'statut',
        'erreur',
        'envoye_at',
    ];

    protected $casts = [
        'envoye_at' => 'datetime',
    ];

    // Constantes pour les types
    public const TYPE_PREINSCRIPTION_RECUE = 'preinscription_recue';
    public const TYPE_PRESELECTIONNE       = 'preselectionne';
    public const TYPE_DOSSIER_RECU         = 'dossier_recu';
    public const TYPE_ACCEPTE              = 'accepte';
    public const TYPE_REFUSE               = 'refuse';
    public const TYPE_RAPPEL_VEILLE        = 'rappel_veille';
    public const TYPE_REMERCIEMENT         = 'remerciement';
    public const TYPE_RAPPEL_DOSSIER       = 'rappel_dossier';

    // Constantes pour les statuts
    public const STATUT_QUEUED = 'queued';
    public const STATUT_SENT   = 'sent';
    public const STATUT_FAILED = 'failed';

    /**
     * Relation : email d'une inscription.
     */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Marquer comme envoyé.
     */
    public function marquerEnvoye(): void
    {
        $this->update([
            'statut'    => self::STATUT_SENT,
            'envoye_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué.
     */
    public function marquerEchec(string $erreur): void
    {
        $this->update([
            'statut' => self::STATUT_FAILED,
            'erreur' => $erreur,
        ]);
    }

    /**
     * Libellés humains pour l'affichage.
     */
    public static function labelType(string $type): string
    {
        return match ($type) {
            self::TYPE_PREINSCRIPTION_RECUE => 'Pré-inscription reçue',
            self::TYPE_PRESELECTIONNE       => 'Présélectionné(e)',
            self::TYPE_DOSSIER_RECU         => 'Dossier reçu',
            self::TYPE_ACCEPTE              => 'Candidature acceptée',
            self::TYPE_REFUSE               => 'Candidature refusée',
            self::TYPE_RAPPEL_VEILLE        => 'Rappel veille',
            self::TYPE_REMERCIEMENT         => 'Remerciement post-événement',
            self::TYPE_RAPPEL_DOSSIER       => 'Rappel dossier à compléter',
            default                          => $type,
        };
    }
}