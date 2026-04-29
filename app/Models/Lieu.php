<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lieu extends Model
{
    use HasFactory;

    protected $table = 'lieux';

    protected $fillable = [
        'nom',
        'adresse',
        'coordonnees_gps',
    ];

    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class);
    }
}