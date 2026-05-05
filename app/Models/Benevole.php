<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Benevole extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'nom', 'prenom',
        'telephone', 'email',
        'poste_affecte', 'creneaux_horaires',
        'statut',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }
}