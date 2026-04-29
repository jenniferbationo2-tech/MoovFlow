<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use App\Models\Paiement;
use App\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaiementController extends Controller
{
    public function __construct(
        private readonly PaiementService $paiementService
    ) {
    }

    public function initier(Request $request, Inscription $inscription): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['nullable', Rule::in(['cash', 'moov_money'])],
        ]);

        $payload = $this->paiementService->initierPaiement($inscription, $validated['mode'] ?? 'moov_money');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Paiement initie avec succes.',
                'paiement_id' => $payload['paiement']->id,
                'payment_url' => $payload['payment_url'],
            ]);
        }

        return redirect()
            ->route('inscriptions.show', $inscription)
            ->with('success', 'Paiement initie. Reference: '.($payload['paiement']->reference_transaction ?? 'Aucune'));
    }

    public function callback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'statut' => ['required', Rule::in(['paye', 'echec'])],
        ]);

        if ($validated['statut'] === 'echec') {
            $paiement = Paiement::query()
                ->where('reference_transaction', $validated['reference'])
                ->first();

            if (! $paiement) {
                return response()->json([
                    'message' => 'Reference introuvable.',
                ], 404);
            }

            $paiement->update([
                'statut' => 'echec',
            ]);

            return response()->json([
                'message' => 'Paiement marque en echec.',
            ]);
        }

        $paiement = $this->paiementService->confirmerPaiement($validated['reference']);

        try {
            Mail::raw(
                'Votre paiement pour '.$paiement->inscription->evenement->titre.' a ete confirme. Reference: '.$paiement->reference_transaction,
                function ($message) use ($paiement): void {
                    $message
                        ->to($paiement->inscription->user->email)
                        ->subject('Confirmation de paiement MOOT Event');
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('Envoi d email de confirmation impossible.', [
                'paiement_id' => $paiement->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Paiement confirme avec succes.',
            'paiement_id' => $paiement->id,
            'facture_url' => $paiement->facture?->url_pdf ? Storage::url($paiement->facture->url_pdf) : null,
        ]);
    }

    public function show(Paiement $paiement): Response
    {
        $paiement->load(['inscription.user', 'inscription.evenement', 'inscription.tarif', 'facture']);

        return Inertia::render('Paiements/Show', [
            'paiement' => [
                'id' => $paiement->id,
                'montant' => (float) $paiement->montant,
                'mode' => $paiement->mode,
                'statut' => $paiement->statut,
                'reference' => $paiement->reference_transaction,
                'created_at' => optional($paiement->created_at)?->toIso8601String(),
                'facture' => $paiement->facture ? [
                    'numero' => $paiement->facture->numero_facture,
                    'url' => $paiement->facture->url_pdf ? Storage::url($paiement->facture->url_pdf) : null,
                ] : null,
                'inscription' => [
                    'id' => $paiement->inscription?->id,
                    'participant' => $paiement->inscription?->user?->name,
                    'email' => $paiement->inscription?->user?->email,
                    'evenement' => $paiement->inscription?->evenement?->titre,
                    'tarif' => $paiement->inscription?->tarif?->nom ?? 'Gratuit',
                ],
            ],
        ]);
    }
}