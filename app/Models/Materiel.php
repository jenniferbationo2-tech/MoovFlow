<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materiel extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'categorie', 'description',
        'quantite_totale', 'quantite_disponible',
        'unite', 'etat', 'photo', 'lieu_stockage', 'note',
    ];

    protected $casts = [
        'quantite_totale'     => 'integer',
        'quantite_disponible' => 'integer',
    ];

    // ──── CONSTANTES ────
    public const CATEGORIES = [
        'audiovisuel'   => 'Audiovisuel',
        'mobilier'      => 'Mobilier',
        'signaletique'  => 'Signalétique',
        'informatique'  => 'Informatique',
        'restauration'  => 'Restauration',
        'securite'      => 'Sécurité',
        'goodies'       => 'Goodies / Communication',
        'fourniture'    => 'Fournitures bureau',
        'autre'         => 'Autre',
    ];

    public const ETATS = [
        'neuf'  => 'Neuf',
        'bon'   => 'Bon état',
        'usage' => 'Usagé',
        'hs'    => 'Hors service',
    ];

    public function evenements(): BelongsToMany
    {
        return $this->belongsToMany(Evenement::class, 'evenement_materiel')
                    ->withPivot(['quantite_prevue', 'quantite_sortie', 'quantite_retournee', 'statut', 'note'])
                    ->withTimestamps();
    }

    // Helpers
    public function labelCategorie(): string
    {
        return self::CATEGORIES[$this->categorie] ?? $this->categorie;
    }

    public function labelEtat(): string
    {
        return self::ETATS[$this->etat] ?? $this->etat;
    }
}