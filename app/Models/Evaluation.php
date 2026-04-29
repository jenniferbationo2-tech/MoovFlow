<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_id',
        'jury_id',
        'note',
        'commentaire',
    ];

    protected $casts = [
        'note' => 'decimal:2',
    ];

    public function projet(): BelongsTo
    {
        return $this->belongsTo(Projet::class);
    }

    public function jury(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jury_id');
    }
}