<?php

namespace App\Services;

use App\Exports\FinancierExport;
use App\Exports\ParticipationExport;
use App\Exports\RseExport;
use App\Models\Evenement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{
    public function __construct(
        private readonly DashboardAggregatorService $dashboardAggregatorService
    ) {
    }

    public function exportPDF(Evenement $evenement, string $type): Response
    {
        $stats = $this->dashboardAggregatorService->getEventStats($evenement);
        $view = match ($type) {
            'participation' => 'pdf.rapport-participation',
            'financier' => 'pdf.rapport-financier',
            'rse' => 'pdf.rapport-rse',
            default => null,
        };

        abort_if($view === null, 422, 'Type de rapport PDF non pris en charge.');

        $pdf = Pdf::loadView($view, [
            'evenement' => $evenement,
            'stats' => $stats[$type],
        ]);

        return $pdf->download('rapport-'.$type.'-evenement-'.$evenement->id.'.pdf');
    }

    public function exportExcel(Evenement $evenement, string $type): BinaryFileResponse
    {
        $export = match ($type) {
            'participation' => new ParticipationExport($evenement),
            'financier' => new FinancierExport($evenement),
            'rse' => new RseExport($evenement),
            default => null,
        };

        abort_if($export === null, 422, 'Type de rapport Excel non pris en charge.');

        return Excel::download($export, 'rapport-'.$type.'-evenement-'.$evenement->id.'.xlsx');
    }

    public function exportPresentation(Evenement $evenement): Response
    {
        $stats = $this->dashboardAggregatorService->getEventStats($evenement);
        $html = View::make('pdf.rapport-presentation', [
            'evenement' => $evenement,
            'stats' => $stats,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="presentation-evenement-'.$evenement->id.'.html"',
        ]);
    }
}