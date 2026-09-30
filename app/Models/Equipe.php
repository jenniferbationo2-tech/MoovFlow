<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'nom',
        'capitaine',
        'categorie',
        'type',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function membres(): HasMany
    {
        return $this->hasMany(EquipeMembre::class);
    }
}