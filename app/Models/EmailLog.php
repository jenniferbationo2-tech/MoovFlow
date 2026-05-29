<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    public const TYPE_PREINSCRIPTION_RECUE  = 'preinscription_recue';
    public const TYPE_PRESELECTIONNE        = 'preselectionne';
    public const TYPE_PRESELECTION_RECUE    = 'preselection_recue';
    public const TYPE_DOSSIER_RECU          = 'dossier_recu';
    public const TYPE_DOSSIER_INCOMPLET     = 'dossier_incomplet';
    public const TYPE_DOSSIER_VALIDE        = 'dossier_valide';
    public const TYPE_INSCRIPTION_ACCEPTEE  = 'inscription_acceptee';
    public const TYPE_INSCRIPTION_CONFIRMEE = 'inscription_confirmee';
    public const TYPE_INSCRIPTION_REFUSEE   = 'inscription_refusee';
    public const TYPE_ACCEPTE               = 'accepte';
    public const TYPE_REFUSE                = 'refuse';
    public const TYPE_RAPPEL_VEILLE         = 'rappel_veille';
    public const TYPE_RAPPEL_EVENEMENT      = 'rappel_evenement';
    public const TYPE_RAPPEL_DOSSIER        = 'rappel_dossier';
    public const TYPE_REMERCIEMENT          = 'remerciement';
    public const TYPE_PRESENCE_CONFIRMEE    = 'presence_confirmee';
    public const TYPE_CAMPAGNE              = 'campagne';
    public const TYPE_CERTIFICAT            = 'certificat';
    public const TYPE_GENERIQUE             = 'generique';

   
    public const STATUT_QUEUED     = 'queued';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_EN_COURS   = 'en_cours';
    public const STATUT_ENVOYE     = 'envoye';
    public const STATUT_SENT       = 'sent';
    public const STATUT_DELIVERED  = 'delivered';
    public const STATUT_FAILED     = 'failed';
    public const STATUT_ECHEC      = 'echec';
    public const STATUT_OPENED     = 'opened';
    public const STATUT_BOUNCED    = 'bounced';

    protected $table = 'email_logs';

    protected $fillable = [
        'inscription_id',
        'campagne_id',
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

   
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CommunicationCampaign::class, 'campagne_id');
    }

   
    public function getEstReussiAttribute(): bool
    {
        return $this->statut === 'sent';
    }

    public function getEstEchecAttribute(): bool
    {
        return $this->statut === 'failed';
    }

    
    public function marquerEnvoye(): void
    {
        $this->update([
            'statut'    => self::STATUT_ENVOYE,
            'envoye_at' => now(),
            'erreur'    => null,
        ]);
    }

    
    public function marquerEchec(?string $erreur = null): void
    {
        $this->update([
            'statut' => self::STATUT_FAILED,
            'erreur' => $erreur,
        ]);
    }


   
}