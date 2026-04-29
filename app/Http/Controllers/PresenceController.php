<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Presence;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresenceController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService
    ) {
    }

    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $inscription = $this->qrCodeService->verify($validated['code']);

        if (! $inscription) {
            return response()->json([
                'message' => 'QR code invalide.',
            ], 404);
        }

        if ($inscription->statut === 'annule') {
            return response()->json([
                'message' => 'Cette inscription est annulee.',
            ], 422);
        }

        if ((float) ($inscription->tarif?->montant ?? 0) > 0 && $inscription->paiement?->statut !== 'paye') {
            return response()->json([
                'message' => 'Le paiement n est pas encore confirme pour ce participant.',
            ], 422);
        }

        if ($inscription->presence) {
            return response()->json([
                'message' => 'Double scan detecte.',
                'presence' => [
                    'scan_time' => optional($inscription->presence->scan_time)?->toIso8601String(),
                ],
            ], 409);
        }

        $presence = Presence::query()->create([
            'inscription_id' => $inscription->id,
            'scane_par' => $request->user()?->id,
            'scan_time' => now(),
        ]);

        return response()->json([
            'message' => 'Presence enregistree avec succes.',
            'presence' => [
                'id' => $presence->id,
                'participant' => $inscription->user?->name,
                'evenement' => $inscription->evenement?->titre,
                'scan_time' => optional($presence->scan_time)?->toIso8601String(),
            ],
        ]);
    }

    public function index(Evenement $evenement): Response
    {
        $inscriptions = $evenement->inscriptions()
            ->with(['user', 'presence'])
            ->where('statut', '!=', 'annule')
            ->orderByDesc('created_at')
            ->get();

        $presents = $inscriptions->filter(fn ($inscription) => $inscription->presence !== null)->count();
        $total = $inscriptions->count();
        $absents = $total - $presents;

        return Inertia::render('Presences/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'presences' => $inscriptions->map(fn ($inscription): array => [
                'id' => $inscription->id,
                'participant' => $inscription->user?->name,
                'email' => $inscription->user?->email,
                'statut' => $inscription->presence ? 'present' : 'absent',
                'scan_time' => optional($inscription->presence?->scan_time)?->toIso8601String(),
            ])->values()->all(),
            'stats' => [
                'presents' => $presents,
                'absents' => $absents,
                'taux' => $total > 0 ? round(($presents / $total) * 100, 1) : 0,
            ],
        ]);
    }
}