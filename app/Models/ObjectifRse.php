<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectifRse extends Model
{
    use HasFactory;

    protected $table = 'objectifs_rse';

    protected $fillable = [
        'evenement_id',
        'type_impact',
        'nb_beneficiaires_directs',
        'nb_beneficiaires_indirects',
        'nb_associations_soutenues',
        'nb_projets_accompagnes',
        'nb_femmes_beneficiaires',
        'montants_collectes',
        'retombees_partenaires',
        'nb_emplois_crees',
        'score_environnemental',
    ];

    protected $casts = [
        'nb_beneficiaires_directs' => 'integer',
        'nb_beneficiaires_indirects' => 'integer',
        'nb_associations_soutenues' => 'integer',
        'nb_projets_accompagnes' => 'integer',
        'nb_femmes_beneficiaires' => 'integer',
        'nb_emplois_crees' => 'integer',
        'montants_collectes' => 'decimal:2',
        'retombees_partenaires' => 'decimal:2',
        'score_environnemental' => 'decimal:2',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }
}