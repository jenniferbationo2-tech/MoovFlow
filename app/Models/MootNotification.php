<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MootNotification extends Model
{
    use HasFactory;

    protected $table = 'moot_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'canal',
        'titre',
        'message',
        'statut',
        'date_envoi',
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}