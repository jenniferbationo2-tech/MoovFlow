<?php

namespace App\Exports;

use App\Models\Evenement;
use App\Models\Inscription;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EvenementParticipantsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Evenement $evenement
    ) {
    }

    public function collection(): Collection
    {
        return Inscription::query()
            ->with(['user', 'tarif', 'paiement', 'presence'])
            ->where('evenement_id', $this->evenement->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Inscription $inscription): array => [
                'nom' => $inscription->user?->name,
                'email' => $inscription->user?->email,
                'telephone' => $inscription->user?->telephone,
                'tarif' => $inscription->tarif?->nom,
                'montant' => $inscription->tarif?->montant,
                'statut_inscription' => $inscription->statut,
                'statut_paiement' => $inscription->paiement?->statut,
                'reference_paiement' => $inscription->paiement?->reference_transaction,
                'presence' => $inscription->presence ? 'present' : 'absent',
                'date_inscription' => optional($inscription->created_at)?->format('Y-m-d H:i:s'),
            ]);
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Email',
            'Telephone',
            'Tarif',
            'Montant',
            'Statut inscription',
            'Statut paiement',
            'Reference paiement',
            'Presence',
            'Date inscription',
        ];
    }
}