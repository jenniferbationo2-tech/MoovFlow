<?php

namespace App\Exports;

use App\Models\Evenement;
use App\Services\DashboardAggregatorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParticipationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Evenement $evenement
    ) {
    }

    public function collection(): Collection
    {
        $stats = app(DashboardAggregatorService::class)->getEventStats($this->evenement);

        return collect($stats['participation']['participants']);
    }

    public function headings(): array
    {
        return [
            'id',
            'nom',
            'email',
            'telephone',
            'statut_inscription',
            'presence',
            'scan_time',
        ];
    }
}