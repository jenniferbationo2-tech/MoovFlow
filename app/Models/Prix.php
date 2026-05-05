<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prix extends Model
{
    use HasFactory;

    protected $table = 'prix';

    protected $fillable = [
        'evenement_id', 'rang', 'libelle',
        'description', 'valeur_monetaire',
        'nature_prix', 'gagnant_id', 'attribue',
    ];

    protected $casts = [
        'valeur_monetaire' => 'decimal:2',
        'attribue'         => 'boolean',
        'rang'             => 'integer',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function gagnant(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'gagnant_id');
    }
}