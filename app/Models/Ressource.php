<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ressource extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'type',
        'nom',
        'description',
        'quantite',
        'statut',
    ];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }
}