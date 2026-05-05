<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvenementRequest extends FormRequest
{
    /**
     * Autorise la création d'un événement pour un utilisateur authentifié.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Définit les règles de validation complètes du formulaire.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'visuel' => ['nullable', 'image', 'max:5120'],
            'reglement_pdf'          => ['nullable', 'file', 'mimes:pdf', 'max:10240'], 
            'type_evenement_id' => ['required', 'integer', 'exists:types_evenement,id'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'lieu_id' => ['nullable', 'integer', 'exists:lieux,id'],
            'statut' => ['nullable', Rule::in(['brouillon', 'publie', 'en_cours', 'termine', 'annule'])],
            'montant_previsionnel' => ['nullable', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'max:10'],
            'type_impact' => ['nullable', 'string', 'max:100'],
            'nb_beneficiaires_cibles' => ['nullable', 'integer', 'min:0'],
            'nb_beneficiaires_indirects' => ['nullable', 'integer', 'min:0'],
            'nb_associations_soutenues' => ['nullable', 'integer', 'min:0'],
            'nb_projets_accompagnes' => ['nullable', 'integer', 'min:0'],
            'nb_femmes_beneficiaires' => ['nullable', 'integer', 'min:0'],
            'montants_collectes' => ['nullable', 'numeric', 'min:0'],
            'retombees_partenaires' => ['nullable', 'numeric', 'min:0'],
            'nb_emplois_crees' => ['nullable', 'integer', 'min:0'],
            'score_environnemental' => ['nullable', 'numeric', 'between:0,100'],


            'nom_salon_hote'        => ['nullable', 'string', 'max:255'],
            'organisateur_externe'  => ['nullable', 'string', 'max:255'],
            'lieu_stand'            => ['nullable', 'string', 'max:255'],
            'superficie_stand'      => ['nullable', 'integer', 'min:0'],
            'objectifs_stand'       => ['nullable', 'string'],
            'objectif_prospects'    => ['nullable', 'integer', 'min:0'],
        ];
    }
}
