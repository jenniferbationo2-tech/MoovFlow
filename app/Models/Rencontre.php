<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rencontre extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_phase_id',
        'equipe_a_id', 'equipe_b_id',
        'date_match', 'lieu_match', 'arbitre',
        'score_equipe_a', 'score_equipe_b',
        'statut', 'vainqueur_id', 'observations',
    ];

    protected $casts = [
        'date_match'     => 'datetime',
        'score_equipe_a' => 'integer',
        'score_equipe_b' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(CompetitionPhase::class, 'competition_phase_id');
    }

    public function equipeA(): BelongsTo
    {
        return $this->belongsTo(Equipe::class, 'equipe_a_id');
    }

    public function equipeB(): BelongsTo
    {
        return $this->belongsTo(Equipe::class, 'equipe_b_id');
    }

    public function vainqueur(): BelongsTo
    {
        return $this->belongsTo(Equipe::class, 'vainqueur_id');
    }

    /**
     * Détermine automatiquement le vainqueur selon le score.
     */
    public function determinerVainqueur(): ?int
    {
        if ($this->score_equipe_a === null || $this->score_equipe_b === null) {
            return null;
        }

        if ($this->score_equipe_a > $this->score_equipe_b) {
            return $this->equipe_a_id;
        }

        if ($this->score_equipe_b > $this->score_equipe_a) {
            return $this->equipe_b_id;
        }

        return null; // Match nul
    }
}