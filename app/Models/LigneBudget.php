<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneBudget extends Model
{
    use HasFactory;

    protected $table = 'lignes_budget';

    protected $fillable = [
        'budget_id',
        'libelle',
        'montant',
        'type',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}