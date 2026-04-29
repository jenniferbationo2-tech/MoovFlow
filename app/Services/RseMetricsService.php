<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\ObjectifRse;
use Illuminate\Support\Collection;

class RseMetricsService
{
    /**
     * Calcule les indicateurs RSE consolidés d'un événement.
     *
     * @return array<string, mixed>
     */
    public function calculate(Evenement $evenement): array
    {
        $objectifs = $evenement->relationLoaded('objectifsRse')
            ? $evenement->objectifsRse
            : $evenement->objectifsRse()->get();

        $social = $this->calculateSocialImpact($objectifs);
        $environmental = $this->calculateEnvironmentalImpact($objectifs);
        $economic = $this->calculateEconomicImpact($objectifs);
        $scoreGlobal = round((($social['score'] * 0.4) + ($environmental['score'] * 0.3) + ($economic['score'] * 0.3)), 1);

        return [
            'social' => $social,
            'environmental' => $environmental,
            'economic' => $economic,
            'score_global' => $scoreGlobal,
            'objectifs' => $objectifs->map(fn (ObjectifRse $objectif): array => [
                'id' => $objectif->id,
                'type_impact' => $objectif->type_impact,
                'nb_beneficiaires_directs' => (int) $objectif->nb_beneficiaires_directs,
                'nb_beneficiaires_indirects' => (int) $objectif->nb_beneficiaires_indirects,
                'nb_associations_soutenues' => (int) $objectif->nb_associations_soutenues,
                'nb_projets_accompagnes' => (int) $objectif->nb_projets_accompagnes,
                'nb_femmes_beneficiaires' => (int) $objectif->nb_femmes_beneficiaires,
                'montants_collectes' => (float) $objectif->montants_collectes,
                'retombees_partenaires' => (float) $objectif->retombees_partenaires,
                'nb_emplois_crees' => (int) $objectif->nb_emplois_crees,
                'score_environnemental' => (float) ($objectif->score_environnemental ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * @param Collection<int, ObjectifRse> $objectifs
     * @return array<string, mixed>
     */
    private function calculateSocialImpact(Collection $objectifs): array
    {
        $beneficiairesDirects = (int) $objectifs->sum('nb_beneficiaires_directs');
        $beneficiairesIndirects = (int) $objectifs->sum('nb_beneficiaires_indirects');
        $associations = (int) $objectifs->sum('nb_associations_soutenues');
        $projets = (int) $objectifs->sum('nb_projets_accompagnes');
        $femmes = (int) $objectifs->sum('nb_femmes_beneficiaires');
        $tauxFemmes = $beneficiairesDirects > 0
            ? round(($femmes / $beneficiairesDirects) * 100, 1)
            : 0.0;

        $score = round(min(100, (
            min(40, $beneficiairesDirects / 5) +
            min(20, $beneficiairesIndirects / 10) +
            min(15, $associations * 3) +
            min(15, $projets * 3) +
            min(10, $tauxFemmes / 10)
        )), 1);

        return [
            'nb_beneficiaires_directs' => $beneficiairesDirects,
            'nb_beneficiaires_indirects' => $beneficiairesIndirects,
            'nb_associations' => $associations,
            'nb_projets' => $projets,
            'nb_femmes_beneficiaires' => $femmes,
            'taux_femmes' => $tauxFemmes,
            'score' => $score,
        ];
    }

    /**
     * @param Collection<int, ObjectifRse> $objectifs
     * @return array<string, mixed>
     */
    private function calculateEnvironmentalImpact(Collection $objectifs): array
    {
        $averageScore = round((float) $objectifs->avg('score_environnemental'), 1);

        return [
            'score_environnemental' => $averageScore,
            'score' => $averageScore,
        ];
    }

    /**
     * @param Collection<int, ObjectifRse> $objectifs
     * @return array<string, mixed>
     */
    private function calculateEconomicImpact(Collection $objectifs): array
    {
        $montantsCollectes = round((float) $objectifs->sum('montants_collectes'), 2);
        $retombeesPartenaires = round((float) $objectifs->sum('retombees_partenaires'), 2);
        $emploisCrees = (int) $objectifs->sum('nb_emplois_crees');

        $score = round(min(100, (
            min(45, $montantsCollectes / 10000) +
            min(35, $retombeesPartenaires / 10000) +
            min(20, $emploisCrees * 4)
        )), 1);

        return [
            'montants_collectes' => $montantsCollectes,
            'retombees_partenaires' => $retombeesPartenaires,
            'nb_emplois_crees' => $emploisCrees,
            'score' => $score,
        ];
    }
}