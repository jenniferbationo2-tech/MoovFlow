<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\NotificationVue;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationsAggregatorService
{
    /**
     * Construit la liste des notifications d'action pour un utilisateur, selon son rôle.
     * Chaque notification actionnable porte une "cle" stable et une "valeur" (compteur) :
     * cela permet de savoir si elle a déjà été vue par l'utilisateur (cf. marquerVues())
     * et de la re-signaler seulement si sa valeur a changé depuis la dernière vue.
     */
    public function getNotifications(User $user, string $role): array
    {
        $notifications = [];

        if ($role === 'responsable_dcirp') {

            // Brouillons créés par des organisateurs : le responsable peut les publier directement
            $brouillonsOrgaQuery = Evenement::where('statut', 'brouillon')
                ->whereHas('createur.roles', fn ($q) => $q->where('name', 'organisateur'));
            $brouillonsOrga = $brouillonsOrgaQuery->count();
            if ($brouillonsOrga > 0) {
                $premierBrouillon = (clone $brouillonsOrgaQuery)->latest()->first();
                $notifications[] = [
                    'cle'   => 'brouillons_organisateur',
                    'valeur'=> $brouillonsOrga,
                    'type'  => 'warning',
                    'titre' => $brouillonsOrga === 1
                        ? "1 événement à publier : « " . Str::limit($premierBrouillon->titre, 40) . " »"
                        : "{$brouillonsOrga} événements créés par des organisateurs, en attente de publication",
                    'href'  => $brouillonsOrga === 1
                        ? "/evenements/{$premierBrouillon->id}"
                        : '/evenements?statut=brouillon',
                    'icon'  => 'document-check',
                ];
            }

            // Événements ayant explicitement demandé une validation
            $aValiderQuery = Evenement::where('statut', 'en_validation');
            $aValider = $aValiderQuery->count();
            if ($aValider > 0) {
                $premierEv = (clone $aValiderQuery)->first();
                $notifications[] = [
                    'cle'     => 'evenements_a_valider',
                    'valeur'  => $aValider,
                    'type'    => 'warning',
                    'titre'   => $aValider === 1
                        ? "1 événement à valider : « " . Str::limit($premierEv->titre, 40) . " »"
                        : "{$aValider} événements à valider",
                    'href'    => $aValider === 1
                        ? "/evenements/{$premierEv->id}"
                        : '/evenements?statut=en_validation',
                    'icon'    => 'document-check',
                ];
            }

            // Dossiers recommandés en attente de validation finale (responsable_dcirp)
            $dossiersRecommandes = Inscription::where('statut', Inscription::STATUT_RECOMMANDEE)->count();
            if ($dossiersRecommandes > 0) {
                $notifications[] = [
                    'cle'   => 'dossiers_recommandes',
                    'valeur'=> $dossiersRecommandes,
                    'type'  => 'warning',
                    'titre' => "{$dossiersRecommandes} candidature" . ($dossiersRecommandes > 1 ? 's' : '') . " recommandée" . ($dossiersRecommandes > 1 ? 's' : '') . " — validation finale requise",
                    'href'  => '/inscriptions?statut=recommandee',
                    'icon'  => 'check',
                ];
            }

            // Dossiers soumis en attente d'analyse
            $dossiersAttente = Inscription::whereIn('statut', [
                Inscription::STATUT_DOSSIER_SOUMIS,
                Inscription::STATUT_EN_ANALYSE,
                Inscription::STATUT_PREINSCRIT,
            ])->count();
            if ($dossiersAttente > 0) {
                $notifications[] = [
                    'cle'     => 'dossiers_en_attente',
                    'valeur'  => $dossiersAttente,
                    'type'    => 'info',
                    'titre'   => "{$dossiersAttente} dossier" . ($dossiersAttente > 1 ? 's' : '') . " en attente d'analyse",
                    'href'    => '/inscriptions',
                    'icon'    => 'inbox',
                ];
            }

        }

        if ($role === 'admin') {
            // Rôle technique : uniquement la sécurité des comptes (l'admin ne gère
            // aucun domaine métier — événements, RSE, logistique...).
            $comptesBloques = User::whereNotNull('bloque_jusqu_a')
                ->where('bloque_jusqu_a', '>', now())
                ->count();
            if ($comptesBloques > 0) {
                $notifications[] = [
                    'cle'     => 'comptes_bloques',
                    'valeur'  => $comptesBloques,
                    'type'    => 'danger',
                    'titre'   => "{$comptesBloques} compte" . ($comptesBloques > 1 ? 's' : '') . " bloqué" . ($comptesBloques > 1 ? 's' : ''),
                    'href'    => '/admin/security',
                    'icon'    => 'shield',
                ];
            }
        }

        if ($role === 'organisateur') {

            // Événements avec demandes de modifications
            $avecModifsQuery = Evenement::where('created_by', $user->id)
                ->where('statut', 'brouillon')
                ->whereNotNull('modifications_demandees');
            $avecModifs = $avecModifsQuery->count();
            if ($avecModifs > 0) {
                $premier = (clone $avecModifsQuery)->first();
                $notifications[] = [
                    'cle'     => 'modifications_demandees',
                    'valeur'  => $avecModifs,
                    'type'    => 'warning',
                    'titre'   => $avecModifs === 1
                        ? "Modifications demandées pour « " . Str::limit($premier->titre, 30) . " »"
                        : "{$avecModifs} événements avec modifications à apporter",
                    'href'    => $avecModifs === 1
                        ? "/evenements/{$premier->id}"
                        : '/evenements?statut=brouillon',
                    'icon'    => 'edit',
                ];
            }

            // Brouillons à finaliser
            $brouillons = Evenement::where('created_by', $user->id)
                ->where('statut', 'brouillon')->count();
            if ($brouillons > 0) {
                $notifications[] = [
                    'cle'     => 'brouillons_a_finaliser',
                    'valeur'  => $brouillons,
                    'type'    => 'warning',
                    'titre'   => "{$brouillons} brouillon" . ($brouillons > 1 ? 's' : '') . " à finaliser",
                    'href'    => '/evenements?statut=brouillon',
                    'icon'    => 'edit',
                ];
            }

            // Dossiers en attente pour SES événements
            $dossiersMienAttente = Inscription::whereHas('evenement',
                fn ($q) => $q->where('created_by', $user->id))
                ->whereIn('statut', ['en_attente', 'en_analyse'])
                ->count();
            if ($dossiersMienAttente > 0) {
                $notifications[] = [
                    'cle'     => 'dossiers_a_analyser',
                    'valeur'  => $dossiersMienAttente,
                    'type'    => 'info',
                    'titre'   => "{$dossiersMienAttente} candidature" . ($dossiersMienAttente > 1 ? 's' : '') . " à analyser",
                    'href'    => '/inscriptions?statut=en_attente',
                    'icon'    => 'inbox',
                ];
            }
        }

        // Une notification est "nouvelle" (et compte dans le badge) si elle n'a jamais
        // été vue, ou si sa valeur a changé depuis la dernière fois qu'elle a été vue.
        if (!empty($notifications)) {
            $cles = array_column($notifications, 'cle');
            $vues = NotificationVue::where('user_id', $user->id)
                ->whereIn('cle', $cles)
                ->get()
                ->keyBy('cle');

            foreach ($notifications as &$notif) {
                $vue = $vues->get($notif['cle']);
                $notif['nouveau'] = !$vue || (int) $vue->derniere_valeur !== (int) $notif['valeur'];
            }
            unset($notif);
        }

        // Si aucune notification → message positif
        if (empty($notifications)) {
            $notifications[] = [
                'type'    => 'success',
                'titre'   => 'Tout est à jour !',
                'href'    => null,
                'icon'    => 'check',
                'nouveau' => false,
            ];
        }

        return $notifications;
    }

    /**
     * Marque comme "vues" toutes les notifications actuelles de l'utilisateur
     * (appelé quand il ouvre la cloche) : elles ne re-compteront dans le badge
     * que si leur valeur change par la suite.
     */
    public function marquerVues(User $user, string $role): void
    {
        $notifications = $this->getNotifications($user, $role);

        foreach ($notifications as $notif) {
            if (!isset($notif['cle'])) {
                continue;
            }

            NotificationVue::updateOrCreate(
                ['user_id' => $user->id, 'cle' => $notif['cle']],
                ['derniere_valeur' => $notif['valeur'], 'vu_le' => now()]
            );
        }
    }

    /**
     * Détermine le rôle "principal" utilisé pour le routage des dashboards/notifications.
     */
    public function resolvePrimaryRole(User $user): string
    {
        $roles = $user->roles->pluck('name')->all();

        if (in_array('admin', $roles)) {
            return 'admin';
        }
        if (in_array('responsable_dcirp', $roles)) {
            return 'responsable_dcirp';
        }
        if (in_array('organisateur', $roles)) {
            return 'organisateur';
        }

        return 'participant';
    }
}
