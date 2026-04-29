<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_id',
        'scane_par',
        'scan_time',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function scanneur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scane_par');
    }
}