<?php

namespace App\Policies;

use App\Models\Inscription;
use App\Models\User;

class InscriptionPolicy
{
    /**
     * La liste globale est reservee a l administration et a l organisation.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);
    }

    /**
     * Consultation detaillee autorisee pour les roles metier ou le participant concerne.
     */
    public function view(User $user, Inscription $inscription): bool
    {
         if ($user->hasAnyRole(['admin', 'responsable_dcirp'])) {
        return true;
    }

    // Organisateur → uniquement les dossiers de ses événements
    if ($user->hasRole('organisateur')) {
        return (int) $inscription->evenement?->created_by === (int) $user->id;
    }


    return $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur'])
            || (int) $inscription->user_id === (int) $user->id;

            
    }

    /**
     * Seul un participant peut creer sa propre inscription.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('participant');
    }

    /**
     * Suppression possible par l administration ou par le participant lui meme.
     */
    public function delete(User $user, Inscription $inscription): bool
    {
        return $user->hasRole('admin')
            || (int) $inscription->user_id === (int) $user->id;
    }

    /**
     * Export reserve aux roles d administration et d organisation.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);
    }

public function update(User $user, Inscription $inscription): bool
{
    if ($user->hasAnyRole(['admin', 'responsable_dcirp'])) {
        return true;
    }
    // Organisateur → uniquement ses événements
    return $user->hasRole('organisateur')
        && (int) $inscription->evenement?->created_by === (int) $user->id;
}



}