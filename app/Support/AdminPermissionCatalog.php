<?php

namespace App\Support;

class AdminPermissionCatalog
{
    /**
     * Retourne les permissions groupees par module.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Evenements' => [
                'evenements.view' => 'Consulter les evenements',
                'evenements.create' => 'Creer des evenements',
                'evenements.edit' => 'Modifier les evenements',
                'evenements.delete' => 'Supprimer les evenements',
                'evenements.publish' => 'Publier les evenements',
            ],
            'Inscriptions' => [
                'inscriptions.view' => 'Consulter les inscriptions',
                'inscriptions.create' => 'Creer une inscription',
                'inscriptions.export' => 'Exporter les inscriptions',
            ],
            'Logistique' => [
                'logistique.view' => 'Consulter la logistique',
                'logistique.manage' => 'Piloter la logistique',
            ],
            'Communication' => [
                'communication.view' => 'Consulter la communication',
                'communication.send' => 'Envoyer des communications',
                'communication.manage' => 'Administrer la communication',
            ],
            'Rapports' => [
                'rapports.view' => 'Consulter les rapports',
                'rapports.export' => 'Exporter les rapports',
            ],
            'CRM' => [
                'crm.view' => 'Consulter le CRM',
                'crm.manage' => 'Administrer le CRM',
                'crm.sync' => 'Synchroniser le CRM',
            ],
            'Administration' => [
                'admin.users' => 'Gerer les utilisateurs',
                'admin.roles' => 'Gerer les roles',
                'admin.audit' => 'Consulter l audit',
                'admin.settings' => 'Gerer les parametres',
            ],
        ];
    }

    /**
     * Retourne la liste plate de toutes les permissions.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(array_merge(...array_values(self::grouped())));
    }

    /**
     * Retourne les permissions attribuees a chaque role.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleAssignments(): array
    {
        return [
            'admin' => self::all(),
            'responsable_dcirp' => array_values(array_filter(
                self::all(),
                fn (string $permission): bool => ! str_starts_with($permission, 'admin.')
            )),
            'organisateur' => array_values(array_merge(
                self::permissionsByPrefix('evenements.'),
                self::permissionsByPrefix('inscriptions.'),
                self::permissionsByPrefix('logistique.'),
                ['communication.view', 'communication.send', 'communication.manage', 'rapports.view']
            )),
            'participant' => [
                'inscriptions.create',
            ],
            'intervenant' => [
                'evenements.view',
                'logistique.view',
            ],
            'benevole' => [
                'evenements.view',
                'inscriptions.view',
            ],
            'jury' => [
                'evenements.view',
            ],
        ];
    }

    /**
     * Retourne les permissions d un prefixe donne.
     *
     * @return array<int, string>
     */
    public static function permissionsByPrefix(string $prefix): array
    {
        return array_values(array_filter(
            self::all(),
            fn (string $permission): bool => str_starts_with($permission, $prefix)
        ));
    }
}