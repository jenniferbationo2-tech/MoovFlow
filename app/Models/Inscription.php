<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inscription extends Model
{

    public const STATUT_PREINSCRIT      = 'preinscrit';
    public const STATUT_PRESELECTIONNE  = 'preselectionne';
    public const STATUT_DOSSIER_SOUMIS  = 'dossier_soumis';
    public const STATUT_EN_ANALYSE      = 'en_analyse';
    public const STATUT_RECOMMANDEE     = 'recommandee';
    public const STATUT_ACCEPTEE        = 'acceptee';
    public const STATUT_REFUSEE         = 'refusee';
    public const STATUT_CONFIRMEE       = 'confirmee';
    public const STATUT_PRESENT         = 'present';
    public const STATUT_ABSENT          = 'absent';
    public const STATUT_ANNULEE         = 'annulee';

    // Niveaux d'inscription
    public const NIVEAU_1 = 'niveau_1';
    public const NIVEAU_2 = 'niveau_2';

    public const TYPES_SANS_PRESELECTION = ['CONF', 'FORMATION'];

    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'evenement_id', 'tarif_id',
        'statut', 'qr_code',
        'motif_refus', 'date_analyse', 'analyse_par',
        'niveau_inscription',
        'presele_par_id', 'presele_le',
        'recommande_par_id', 'recommande_le', 'note_organisateur',
        'valide_par_id', 'valide_le',
        'motif_refus',
        'token_acces', 'token_expire_at',
        'check_in_at', 'check_in_par_id',
    ];

    protected $casts = [
        'date_analyse' => 'datetime',
        'presele_le'       => 'datetime',
        'recommande_le'    => 'datetime',
        'valide_le'        => 'datetime',
        'token_expire_at'  => 'datetime',
        'check_in_at'      => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inscription')
            ->logOnly(['user_id', 'evenement_id', 'tarif_id', 'statut', 'qr_code'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Inscription {$eventName}");
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class)->latestOfMany();
    }

    public function presence(): HasOne
    {
        return $this->hasOne(Presence::class);
    }

    public function dossier(): HasOne
    {
        return $this->hasOne(DossierInscription::class);
    }

    public function analysePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyse_par');
    }


    public function estEnAttente(): bool
    {
        return in_array($this->statut, ['en_attente', 'en_analyse']);
    }

    public function estAcceptee(): bool
    {
        return in_array($this->statut, ['acceptee', 'confirmee', 'present']);
    }

    public function estRefusee(): bool
    {
        return $this->statut === 'refusee';
    }

    public function estConfirmee(): bool
    {
        return $this->statut === 'confirmee';
    }

    public function estPresent(): bool
    {
        return $this->statut === 'present';
    }

    public function preseleParUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'presele_par_id');
    }

    public function recommandeParUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recommande_par_id');
    }

    public function valideParUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function checkInParUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'check_in_par_id');
    }


    public function emailLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    // ════════════════════════════════════════
    //   HELPERS WORKFLOW
    // ════════════════════════════════════════

   
    public function necessitePreselection(): bool
    {
        $typeCode = $this->evenement?->typeEvenement?->code;
        return !in_array($typeCode, self::TYPES_SANS_PRESELECTION);
    }

    
    public function peutEtrePreselectionne(): bool
    {
        return $this->statut === self::STATUT_PREINSCRIT
            && $this->necessitePreselection();
    }

    
    public function peutEtreRecommandee(): bool
    {
        // Types sans présélection (CONF, FORMATION) : l'organisateur peut recommander depuis preinscrit
        if (!$this->necessitePreselection()) {
            return in_array($this->statut, [
                self::STATUT_PREINSCRIT,
                self::STATUT_DOSSIER_SOUMIS,
                self::STATUT_EN_ANALYSE,
            ]);
        }

        // Autres types : il faut que le dossier ait été soumis
        return in_array($this->statut, [
            self::STATUT_DOSSIER_SOUMIS,
            self::STATUT_EN_ANALYSE,
        ]);
    }

    
    public function peutEtreValidee(): bool
    {
        // Type CONF/FORMATION : validation directe possible
        if (!$this->necessitePreselection()) {
            return $this->statut === self::STATUT_PREINSCRIT;
        }

        
        return $this->statut === self::STATUT_RECOMMANDEE;
    }

    
    public function peutEtreRefusee(): bool
    {
        return in_array($this->statut, [
            self::STATUT_PREINSCRIT,
            self::STATUT_PRESELECTIONNE,
            self::STATUT_DOSSIER_SOUMIS,
            self::STATUT_EN_ANALYSE,
            self::STATUT_RECOMMANDEE,
        ]);
    }

    
    public function peutEtreCheckIn(): bool
    {
        return in_array($this->statut, [
            self::STATUT_CONFIRMEE,
            self::STATUT_ACCEPTEE,
        ]) && !$this->check_in_at;
    }

    public function genererTokenAcces(int $dureeJours = 7): string
    {
        $token = \Illuminate\Support\Str::random(48);
        $this->update([
            'token_acces'      => $token,
            'token_expire_at'  => now()->addDays($dureeJours),
        ]);
        return $token;
    }

    
    public function tokenEstValide(): bool
    {
        return $this->token_acces
            && $this->token_expire_at
            && $this->token_expire_at->isFuture();
    }

    public function labelStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_PREINSCRIT      => 'Pré-inscrit',
            self::STATUT_PRESELECTIONNE  => 'Présélectionné',
            self::STATUT_DOSSIER_SOUMIS  => 'Dossier soumis',
            self::STATUT_EN_ANALYSE      => 'En analyse',
            self::STATUT_RECOMMANDEE     => 'Recommandée',
            self::STATUT_ACCEPTEE        => 'Acceptée',
            self::STATUT_REFUSEE         => 'Refusée',
            self::STATUT_CONFIRMEE       => 'Confirmée',
            self::STATUT_PRESENT         => 'Présent',
            self::STATUT_ABSENT          => 'Absent',
            self::STATUT_ANNULEE         => 'Annulée',
            default                       => $this->statut,
        };
    }

    
    public function progression(): int
    {
        return match ($this->statut) {
            self::STATUT_PREINSCRIT      => 15,
            self::STATUT_PRESELECTIONNE  => 30,
            self::STATUT_DOSSIER_SOUMIS  => 50,
            self::STATUT_EN_ANALYSE      => 65,
            self::STATUT_RECOMMANDEE     => 80,
            self::STATUT_ACCEPTEE        => 90,
            self::STATUT_CONFIRMEE       => 100,
            self::STATUT_PRESENT         => 100,
            self::STATUT_REFUSEE         => 0,
            self::STATUT_ANNULEE         => 0,
            self::STATUT_ABSENT          => 0,
            default                       => 0,
        };
    }
}