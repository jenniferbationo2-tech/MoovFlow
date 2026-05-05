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
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id', 'evenement_id', 'tarif_id',
        'statut', 'qr_code',
        'motif_refus', 'date_analyse', 'analyse_par',
    ];

    protected $casts = [
        'date_analyse' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inscription')
            ->logOnly(['user_id', 'evenement_id', 'tarif_id', 'statut', 'qr_code'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Inscription {$eventName}");
    }

    // ── RELATIONS ──────────────────────────────

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

    // ── HELPERS DE STATUT ──────────────────────

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
}