<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'nom', 'prenom', 'email', 'telephone',
        'biographie', 'photo', 'specialite', 'disponibilites',
        'taux_cachet', 'montant_perdiems', 'statut_paiement',
    ];

    protected $casts = [
        'taux_cachet'      => 'decimal:2',
        'montant_perdiems' => 'decimal:2',
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