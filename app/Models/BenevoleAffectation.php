<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenevoleAffectation extends Model
{
    use HasFactory;

    protected $table = 'benevoles_affectations';

    protected $fillable = [
        'evenement_id',
        'user_id',
        'poste',
        'creneau_debut',
        'creneau_fin',
    ];

    protected $casts = [
        'creneau_debut' => 'datetime',
        'creneau_fin' => 'datetime',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}