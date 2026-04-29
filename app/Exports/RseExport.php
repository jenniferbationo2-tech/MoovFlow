<?php

namespace App\Exports;

use App\Models\Evenement;
use App\Services\DashboardAggregatorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RseExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Evenement $evenement
    ) {
    }

    public function collection(): Collection
    {
        $stats = app(DashboardAggregatorService::class)->getEventStats($this->evenement);

        return collect($stats['rse']['objectifs']);
    }

    public function headings(): array
    {
        return [
            'id',
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
    }
}