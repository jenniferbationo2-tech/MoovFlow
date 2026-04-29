<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'evenement_id',
        'type',
        'statut',
        'notes',
        'date_prevue',
        'date_realise',
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'date_realise' => 'date',
    ];

    /**
     * Retourne le contact concerné par l'action de suivi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retourne l'événement lié au suivi si disponible.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }
}