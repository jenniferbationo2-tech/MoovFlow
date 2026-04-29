<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BMeeting extends Model
{
    use HasFactory;

    protected $table = 'b2b_meetings';

    protected $fillable = [
        'evenement_id',
        'organisateur_id',
        'prospect_id',
        'prospect_nom',
        'prospect_email',
        'prospect_societe',
        'objet',
        'date_rdv',
        'lieu',
        'statut',
        'notes',
        'score_lead',
    ];

    protected $casts = [
        'date_rdv' => 'datetime',
    ];

    /**
     * Retourne l'événement salon concerné par le rendez-vous.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    /**
     * Retourne l'organisateur du rendez-vous.
     */
    public function organisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    /**
     * Retourne le prospect lorsqu'il existe déjà dans la base.
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prospect_id');
    }
}