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
        'user_id',
        'evenement_id',
        'tarif_id',
        'statut',
        'qr_code',
    ];

    /**
     * Configure la journalisation d activite.
     */
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
}