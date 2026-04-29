<?php

namespace App\Http\Requests;

class UpdateEvenementRequest extends StoreEvenementRequest
{
    /**
     * Autorise la modification d'un événement pour un utilisateur authentifié.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}