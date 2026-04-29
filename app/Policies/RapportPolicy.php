<?php

namespace App\Policies;

use App\Models\User;

class RapportPolicy
{
    /**
     * La consultation des rapports est reservee aux roles de pilotage.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);
    }

    /**
     * L export des rapports est reserve a l administration.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'responsable_dcirp']);
    }
}