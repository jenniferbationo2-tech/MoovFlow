<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Inscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ParticipantsPdfService
{
    /**
     * Génère le PDF de la liste des participants d'un événement.
     */
    public function genererPdf(Evenement $evenement): Response
    {
        $evenement->load(['typeEvenement', 'lieu']);

        // Récupérer les participants (inscriptions confirmées/présentes)
        $participants = Inscription::query()
            ->where('evenement_id', $evenement->id)
            ->whereIn('statut', [
                'acceptee', 'confirmee', 'present',
                'preinscrit', 'preselectionne', 'dossier_soumis',
                'en_analyse', 'recommandee',
            ])
            ->with('user:id,nom,prenom,email,telephone')
            ->orderBy('statut')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Inscription $i) => [
                'qr_code'      => $i->qr_code,
                'nom'          => $i->user?->nom ?? '—',
                'prenom'       => $i->user?->prenom ?? '',
                'email'        => $i->user?->email ?? '',
                'telephone'    => $i->user?->telephone,
                'statut'       => $i->statut,
                'statut_label' => $i->labelStatut(),
            ])
            ->all();

        $pdf = Pdf::loadView('pdf.liste-participants', [
            'evenement'      => $evenement,
            'participants'   => $participants,
            'dateGeneration' => now()->format('d/m/Y à H:i'),
        ])->setPaper('A4', 'portrait');

        $filename = 'participants_' . str_slug_safe($evenement->titre) . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}

if (!function_exists('str_slug_safe')) {
    function str_slug_safe(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        return trim($text, '-');
    }
}