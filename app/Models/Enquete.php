<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquete extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'titre',
        'type',
        'questions',
        'statut',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseEnquete::class);
    }
}