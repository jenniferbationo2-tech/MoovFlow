<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipeMembre extends Model
{
    use HasFactory;

    protected $table = 'equipe_membres';

    protected $fillable = [
        'equipe_id',
        'user_id',
        'role',
    ];

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}