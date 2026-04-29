<?php

namespace App\Policies;

use App\Models\Evenement;
use App\Models\User;

class EvenementPolicy
{
    /**
     * Tous les utilisateurs authentifies peuvent lister les evenements.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Tous les utilisateurs authentifies peuvent consulter un evenement.
     */
    public function view(User $user, Evenement $evenement): bool
    {
        return $user !== null;
    }

    /**
     * Seuls les roles metier autorises peuvent creer.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);
    }

    /**
     * Mise a jour reservee a l administration metier ou au createur.
     */
    public function update(User $user, Evenement $evenement): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp'])
            || (int) $evenement->created_by === (int) $user->id;
    }

    /**
     * Suppression reservee aux roles d administration.
     */
    public function delete(User $user, Evenement $evenement): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp']);
    }

    /**
     * Publication reservee aux roles d administration.
     */
    public function publish(User $user, Evenement $evenement): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp']);
    }
}