<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salle extends Model
{
    use HasFactory;

    protected $fillable = [
        'lieu_id',
        'nom',
        'capacite',
        'equipements',
    ];

    protected $casts = [
        'equipements' => 'array',
        'capacite' => 'integer',
    ];

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }
}