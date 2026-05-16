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
        'nom', 'adresse', 'ville', 'capacite_max',
        'description', 'photo', 'actif',
    ];

    protected $casts = [
        'capacite_max' => 'integer',
        'actif'        => 'boolean',
    ];

    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class);
    }
}