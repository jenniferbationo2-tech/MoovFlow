<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'objet',
        'contenu',
        'mode_destinataires',
        'emails_personnalises',
        'statut',
        'date_envoi',
        'nb_destinataires',
        'nb_ouvertures',
        'nb_acceptations',
    ];

    protected $casts = [
        'emails_personnalises' => 'array',
        'date_envoi' => 'datetime',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'campaign_id');
    }
}