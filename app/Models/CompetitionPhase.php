<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionPhase extends Model
{
    use HasFactory;

    protected $table = 'competition_phases';

    protected $fillable = [
        'evenement_id', 'nom', 'ordre',
        'date_debut', 'date_fin', 'statut',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
        'ordre'      => 'integer',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function rencontres(): HasMany
    {
        return $this->hasMany(Rencontre::class);
    }

    public function classements(): HasMany
    {
        return $this->hasMany(Classement::class)->orderBy('rang');
    }
}