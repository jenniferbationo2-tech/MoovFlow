<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeEvenement extends Model
{
    use HasFactory;

    protected $table = 'types_evenement';

    protected $fillable = [
        'nom',
        'code',
    ];

    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class, 'type_evenement_id');
    }
}