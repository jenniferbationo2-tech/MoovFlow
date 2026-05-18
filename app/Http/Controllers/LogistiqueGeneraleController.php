<?php

namespace App\Http\Controllers;

use App\Models\Lieu;
use App\Models\Materiel;
use App\Models\PosteBenevole;
use App\Models\Prestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LogistiqueGeneraleController extends Controller
{
    /**
     * Vue générale de la logistique (4 onglets).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403,
            'Accès réservé au staff.'
        );

        return Inertia::render('Logistique/Index', [
            // Données des 4 onglets
            'lieux'         => Lieu::orderBy('nom')->get(),
            'materiels'     => Materiel::orderBy('nom')->get(),
            'prestataires'  => Prestataire::where('actif', true)->orderBy('nom')->get(),
            'postes'        => PosteBenevole::with(['evenement:id,titre', 'candidatures'])
                                ->orderByDesc('created_at')
                                ->get(),

            // Listes de constantes pour les selects
            'categoriesMateriel'     => Materiel::CATEGORIES,
            'etatsMateriel'          => Materiel::ETATS,
            'categoriesPrestataire'  => Prestataire::CATEGORIES,

            // KPIs
            'kpis' => [
                'lieux'          => Lieu::count(),
                'materiels'      => Materiel::count(),
                'prestataires'   => Prestataire::where('actif', true)->count(),
                'postes_ouverts' => PosteBenevole::where('statut', 'ouvert')->count(),
            ],

            // Permissions
            'permissions' => [
                'peut_creer'     => $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
                'peut_supprimer' => $user->hasAnyRole(['admin', 'responsable_dcirp']),
            ],
        ]);
    }
}