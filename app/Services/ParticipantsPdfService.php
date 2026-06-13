<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Inscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ParticipantsPdfService
{
    public function genererPdf(Evenement $evenement): Response
    {
        $evenement->load(['typeEvenement', 'lieu']);

        $slug = mb_strtolower($evenement->titre, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
        $slug = trim($slug, '-');

        $participants = Inscription::query()
            ->where('evenement_id', $evenement->id)
            ->whereIn('statut', [
                'acceptee', 'confirmee', 'present',
                'preinscrit', 'preselectionne',
                'dossier_soumis', 'en_analyse', 'recommandee',
            ])
            ->with('user:id,nom,prenom,email,telephone')
            ->orderBy('statut')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Inscription $i) => [
                'qr_code'      => $i->qr_code ?? '—',
                'nom'          => $i->user?->nom ?? '—',
                'prenom'       => $i->user?->prenom ?? '',
                'email'        => $i->user?->email ?? '',
                'telephone'    => $i->user?->telephone ?? '',
                'statut'       => $i->statut,
                'statut_label' => $i->labelStatut(),
            ])
            ->all();

        $pdf = Pdf::loadView('pdf.liste-participants', [
            'evenement'      => $evenement,
            'participants'   => $participants,
            'dateGeneration' => now()->format('d/m/Y à H:i'),
        ])->setPaper('A4', 'portrait');

        $filename = 'participants_' . $slug . '_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}