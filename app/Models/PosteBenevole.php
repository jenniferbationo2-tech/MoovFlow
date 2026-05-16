<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosteBenevole extends Model
{
    use HasFactory;

    protected $table = 'postes_benevoles';

    protected $fillable = [
        'evenement_id', 'nom_poste', 'categorie',
        'description', 'competences_requises',
        'places_max', 'horaire_debut', 'horaire_fin',
        'statut', 'cree_par_id',
    ];

    protected $casts = [
        'places_max'    => 'integer',
        'horaire_debut' => 'datetime',
        'horaire_fin'   => 'datetime',
    ];

    public const CATEGORIES = [
        'accueil'       => 'Accueil',
        'securite'      => 'Sécurité',
        'technique'     => 'Technique',
        'logistique'    => 'Logistique',
        'communication' => 'Communication',
        'restauration'  => 'Restauration',
        'animation'     => 'Animation',
        'autre'         => 'Autre',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function candidatures(): HasMany
    {
        return $this->hasMany(CandidatureBenevole::class, 'poste_id');
    }

    public function candidaturesAcceptees(): HasMany
    {
        return $this->hasMany(CandidatureBenevole::class, 'poste_id')
                    ->where('statut', 'accepte');
    }

    // Helpers
    public function placesRestantes(): int
    {
        return max(0, $this->places_max - $this->candidaturesAcceptees()->count());
    }

    public function estComplet(): bool
    {
        return $this->placesRestantes() === 0;
    }

    public function labelCategorie(): string
    {
        return self::CATEGORIES[$this->categorie] ?? $this->categorie;
    }
}