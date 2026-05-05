<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classement extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_phase_id', 'equipe_id',
        'points', 'matchs_joues',
        'victoires', 'nuls', 'defaites',
        'difference_buts', 'buts_marques', 'buts_encaisses',
        'rang',
    ];

    protected $casts = [
        'points'           => 'integer',
        'matchs_joues'     => 'integer',
        'victoires'        => 'integer',
        'nuls'             => 'integer',
        'defaites'         => 'integer',
        'difference_buts'  => 'integer',
        'buts_marques'     => 'integer',
        'buts_encaisses'   => 'integer',
        'rang'             => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(CompetitionPhase::class, 'competition_phase_id');
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }
}