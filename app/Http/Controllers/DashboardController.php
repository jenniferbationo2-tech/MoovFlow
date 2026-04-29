<?php

namespace App\Http\Controllers;

use App\Services\DashboardAggregatorService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAggregatorService $dashboardAggregatorService
    ) {
    }

    /**
     * Affiche le dashboard principal avec les statistiques temps réel.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard', $this->dashboardAggregatorService->getGlobalStats());
    }
}