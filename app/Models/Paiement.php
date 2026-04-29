<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Paiement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'inscription_id',
        'montant',
        'mode',
        'statut',
        'reference_transaction',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    /**
     * Configure la journalisation d activite.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('paiement')
            ->logOnly(['inscription_id', 'montant', 'mode', 'statut', 'reference_transaction'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Paiement {$eventName}");
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class);
    }
}