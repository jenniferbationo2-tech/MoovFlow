<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Prestataire extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'categorie',
        'contact_nom', 'email', 'telephone',
        'adresse', 'ville', 'description', 'site_web',
        'note_interne', 'note', 'actif',
    ];

    protected $casts = [
        'note_interne' => 'integer',
        'actif'        => 'boolean',
    ];

    public const CATEGORIES = [
        'traiteur'      => 'Traiteur / Restauration',
        'audio_video'   => 'Audio / Vidéo',
        'securite'      => 'Sécurité',
        'photographie'  => 'Photographie',
        'transport'     => 'Transport',
        'decoration'    => 'Décoration',
        'communication' => 'Communication / Impression',
        'animation'     => 'Animation',
        'nettoyage'     => 'Nettoyage',
        'autre'         => 'Autre',
    ];

    public function evenements(): BelongsToMany
    {
        return $this->belongsToMany(Evenement::class, 'evenement_prestataire')
                    ->withPivot(['prestation', 'montant_prevu', 'montant_final', 'statut', 'contrat_pdf', 'note'])
                    ->withTimestamps();
    }

    public function labelCategorie(): string
    {
        return self::CATEGORIES[$this->categorie] ?? $this->categorie;
    }
}