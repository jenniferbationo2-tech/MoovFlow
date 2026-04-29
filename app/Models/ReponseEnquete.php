<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReponseEnquete extends Model
{
    use HasFactory;

    protected $table = 'reponses_enquete';

    protected $fillable = [
        'enquete_id',
        'user_id',
        'reponses',
    ];

    protected $casts = [
        'reponses' => 'array',
    ];

    public function enquete(): BelongsTo
    {
        return $this->belongsTo(Enquete::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}