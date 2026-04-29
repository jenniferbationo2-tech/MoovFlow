<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'montant_previsionnel',
        'devise',
    ];

    protected $casts = [
        'montant_previsionnel' => 'decimal:2',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function lignesBudget(): HasMany
    {
        return $this->hasMany(LigneBudget::class);
    }
}