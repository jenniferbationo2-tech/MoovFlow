<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'user_id',
        'equipement',
        'date_remise',
        'date_retour',
        'date_retour_prevue',
        'etat_depart',
        'etat_retour',
    ];

    protected $casts = [
        'date_remise' => 'date',
        'date_retour' => 'date',
        'date_retour_prevue' => 'date',
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