<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class FactureService
{
    public function generer(Paiement $paiement): Facture
    {
        $paiement->loadMissing(['inscription.user', 'inscription.evenement', 'inscription.tarif', 'facture']);

        $facture = $paiement->facture;

        if (! $facture) {
            $facture = $paiement->facture()->create([
                'numero_facture' => $this->generateNumero(),
                'url_pdf' => null,
            ]);
        }

        $pdf = Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'paiement' => $paiement,
            'inscription' => $paiement->inscription,
            'participant' => $paiement->inscription->user,
            'evenement' => $paiement->inscription->evenement,
            'tarif' => $paiement->inscription->tarif,
        ]);

        $path = 'factures/'.$facture->numero_facture.'.pdf';

        Storage::disk('public')->put($path, $pdf->output());

        $facture->update([
            'url_pdf' => $path,
        ]);

        return $facture->fresh();
    }

    private function generateNumero(): string
    {
        $prefix = now()->format('Ymd');
        $sequence = Facture::query()
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('FACT-%s-%04d', $prefix, $sequence);
    }
}