<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationVue extends Model
{
    protected $fillable = [
        'user_id',
        'cle',
        'derniere_valeur',
        'vu_le',
    ];

    protected $casts = [
        'vu_le' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
