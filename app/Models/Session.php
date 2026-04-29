<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $table = 'sessions_evenement';

    protected $fillable = [
        'evenement_id',
        'salle_id',
        'titre',
        'description',
        'heure_debut',
        'heure_fin',
    ];

    protected $casts = [
        'heure_debut' => 'datetime',
        'heure_fin' => 'datetime',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function intervenantsSessions(): HasMany
    {
        return $this->hasMany(IntervenantSession::class);
    }

    public function intervenants(): HasMany
    {
        return $this->hasMany(IntervenantSession::class);
    }
}