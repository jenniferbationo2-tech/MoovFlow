<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatureBenevole extends Model
{
    use HasFactory;

    protected $table = 'candidatures_benevoles';

    protected $fillable = [
        'poste_id', 'user_id',
        'motivation', 'experience', 'disponibilites',
        'statut', 'motif_refus',
        'valide_par_id', 'valide_le',
    ];

    protected $casts = [
        'valide_le' => 'datetime',
    ];

    public const STATUT_CANDIDAT = 'candidat';
    public const STATUT_ACCEPTE  = 'accepte';
    public const STATUT_REFUSE   = 'refuse';
    public const STATUT_ANNULE   = 'annule';

    public function poste(): BelongsTo
    {
        return $this->belongsTo(PosteBenevole::class, 'poste_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function valideParUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function labelStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_CANDIDAT => 'En attente',
            self::STATUT_ACCEPTE  => 'Acceptée',
            self::STATUT_REFUSE   => 'Refusée',
            self::STATUT_ANNULE   => 'Annulée',
            default                => $this->statut,
        };
    }
}