<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(
        private readonly FactureService $factureService
    ) {
    }

    /**
     * @return array{paiement:Paiement,payment_url:?string}
     */
    public function initierPaiement(Inscription $inscription, string $mode): array
    {
        $inscription->loadMissing(['tarif', 'paiements.facture']);

        $montant = (float) ($inscription->tarif?->montant ?? 0);
        $existingPaiement = $inscription->paiements()
            ->whereIn('statut', ['initie', 'paye'])
            ->latest()
            ->first();

        if ($existingPaiement) {
            return [
                'paiement' => $existingPaiement->fresh(['facture']),
                'payment_url' => $existingPaiement->reference_transaction
                    ? route('paiements.show', $existingPaiement)
                    : null,
            ];
        }

        $reference = $montant > 0 ? $this->generateReference() : null;

        $paiement = $inscription->paiements()->create([
            'montant' => $montant,
            'mode' => $montant > 0 ? $mode : 'gratuit',
            'statut' => $montant > 0 ? 'initie' : 'paye',
            'reference_transaction' => $reference,
        ]);

        if ($montant <= 0) {
            $inscription->update([
                'statut' => 'valide',
            ]);

            $this->factureService->generer($paiement);
        }

        return [
            'paiement' => $paiement->fresh(['facture']),
            'payment_url' => $reference ? route('paiements.show', $paiement) : null,
        ];
    }

    public function confirmerPaiement(string $reference): Paiement
    {
        return DB::transaction(function () use ($reference): Paiement {
            $paiement = Paiement::query()
                ->with(['inscription.user', 'inscription.evenement', 'inscription.tarif', 'facture'])
                ->where('reference_transaction', $reference)
                ->first();

            if (! $paiement) {
                throw ValidationException::withMessages([
                    'reference' => 'Reference de paiement introuvable.',
                ]);
            }

            if ($paiement->statut !== 'paye') {
                $paiement->update([
                    'statut' => 'paye',
                ]);

                $paiement->inscription->update([
                    'statut' => 'valide',
                ]);
            }

            $this->factureService->generer($paiement);

            return $paiement->fresh(['inscription.user', 'inscription.evenement', 'inscription.tarif', 'facture']);
        });
    }

    private function generateReference(): string
    {
        return sprintf(
            'MOOV-%s-%s',
            now()->format('YmdHis'),
            str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)
        );
    }
}