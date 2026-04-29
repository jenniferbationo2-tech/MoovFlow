<?php

namespace App\Exports;

use App\Models\Evenement;
use App\Services\DashboardAggregatorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancierExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Evenement $evenement
    ) {
    }

    public function collection(): Collection
    {
        $stats = app(DashboardAggregatorService::class)->getEventStats($this->evenement);

        return collect($stats['financier']['budget']['lignes']);
    }

    public function headings(): array
    {
        return [
            'id',
            'libelle',
            'montant',
            'type',
        ];
    }
}