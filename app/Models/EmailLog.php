<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'inscription_id',
        'campagne_id',
        'type',
        'destinataire',
        'sujet',
        'contenu',
        'statut',
        'erreur',
        'envoye_at',
    ];

    protected $casts = [
        'envoye_at' => 'datetime',
    ];

    // ─── RELATIONS ──────────────────────
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CommunicationCampaign::class, 'campagne_id');
    }

    // ─── HELPERS ────────────────────────
    public function getEstReussiAttribute(): bool
    {
        return $this->statut === 'sent';
    }

    public function getEstEchecAttribute(): bool
    {
        return $this->statut === 'failed';
    }
}