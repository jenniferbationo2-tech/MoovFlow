<?php

namespace App\Http\Controllers;

use App\Models\CandidatureBenevole;
use App\Models\PosteBenevole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BenevoleController extends Controller
{

    public function index($evenement)
    {
        return inertia('Logistique/Benevoles/Index', [
            'evenement' => $evenement
        ]);
    }


    /**
     * Candidater à un poste bénévole.
     */
    public function candidater(Request $request, PosteBenevole $poste): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 403, 'Connectez-vous pour candidater.');

        // Seul le rôle 'participant' peut candidater
        abort_unless(
            $user->hasRole('participant'),
            403,
            'Seuls les participants peuvent candidater aux postes bénévoles.'
        );

        // Vérifier que le poste est ouvert
        if ($poste->statut !== 'ouvert') {
            return back()->with('error', 'Ce poste n\'est plus ouvert aux candidatures.');
        }

        // Vérifier qu'il reste des places
        if ($poste->estComplet()) {
            return back()->with('error', 'Ce poste est complet.');
        }

        // Vérifier qu'il n'a pas déjà candidaté
        $dejaCandidate = CandidatureBenevole::where('poste_id', $poste->id)
            ->where('user_id', $user->id)
            ->whereNotIn('statut', ['refuse', 'annule'])
            ->exists();

        if ($dejaCandidate) {
            return back()->with('error', 'Vous avez déjà candidaté à ce poste.');
        }

        $validated = $request->validate([
            'motivation'     => ['required', 'string', 'min:30', 'max:2000'],
            'experience'     => ['nullable', 'string', 'max:1000'],
            'disponibilites' => ['nullable', 'string', 'max:500'],
        ]);

        CandidatureBenevole::create([
            'poste_id'        => $poste->id,
            'user_id'         => $user->id,
            'motivation'      => $validated['motivation'],
            'experience'      => $validated['experience'] ?? null,
            'disponibilites'  => $validated['disponibilites'] ?? null,
            'statut'          => CandidatureBenevole::STATUT_CANDIDAT,
        ]);

        return back()->with('success', 'Votre candidature a été envoyée ! Vous serez notifié(e) de la décision par email.');
    }

    /**
     * Mes candidatures (vue participant).
     */
    public function mesCandidatures(): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $candidatures = CandidatureBenevole::query()
            ->where('user_id', $user->id)
            ->with([
                'poste.evenement.typeEvenement',
                'poste.evenement.lieu',
            ])
            ->latest()
            ->get();

        $stats = [
            'total'      => $candidatures->count(),
            'en_attente' => $candidatures->where('statut', 'candidat')->count(),
            'acceptees'  => $candidatures->where('statut', 'accepte')->count(),
            'refusees'   => $candidatures->where('statut', 'refuse')->count(),
        ];

        return Inertia::render('Benevolat/MesCandidatures', [
            'candidatures' => $candidatures,
            'stats'        => $stats,
        ]);
    }

    /**
     * Annuler sa propre candidature.
     */
    public function annulerCandidature(CandidatureBenevole $candidature): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($candidature->user_id === $user->id, 403);

        if (!in_array($candidature->statut, ['candidat', 'accepte'])) {
            return back()->with('error', 'Cette candidature ne peut plus être annulée.');
        }

        $candidature->update(['statut' => CandidatureBenevole::STATUT_ANNULE]);

        return back()->with('success', 'Candidature annulée.');
    }

    // ════════════════════════════════════════
    //   CÔTÉ STAFF
    // ════════════════════════════════════════

    /**
     * Liste des candidatures pour un poste donné.
     */
    public function listeCandidatures(PosteBenevole $poste): Response
    {
        $this->authorizeStaff($poste);

        $poste->load([
            'evenement.typeEvenement',
            'candidatures.user:id,nom,prenom,email,telephone',
            'candidatures.valideParUser:id,nom,prenom',
        ]);

        $stats = [
            'total'         => $poste->candidatures->count(),
            'en_attente'    => $poste->candidatures->where('statut', 'candidat')->count(),
            'acceptees'     => $poste->candidatures->where('statut', 'accepte')->count(),
            'refusees'      => $poste->candidatures->where('statut', 'refuse')->count(),
            'places_restantes' => $poste->placesRestantes(),
        ];

        return Inertia::render('Benevolat/ListeCandidatures', [
            'poste' => $poste,
            'stats' => $stats,
        ]);
    }

    /**
     * Accepter une candidature.
     */
    public function accepter(CandidatureBenevole $candidature): RedirectResponse
    {
        $this->authorizeStaff($candidature->poste);

        // Vérifier qu'il reste des places
        if ($candidature->poste->placesRestantes() <= 0) {
            return back()->with('error', 'Le poste est complet, impossible d\'accepter plus de candidatures.');
        }

        DB::transaction(function () use ($candidature) {
            $candidature->update([
                'statut'        => CandidatureBenevole::STATUT_ACCEPTE,
                'valide_par_id' => Auth::id(),
                'valide_le'     => now(),
            ]);

            // Si plus de places dispo, fermer le poste
            if ($candidature->poste->placesRestantes() <= 0) {
                $candidature->poste->update(['statut' => 'complet']);
            }
        });

        return back()->with('success', 'Candidature acceptée. Le bénévole a été notifié.');
    }

    /**
     * Refuser une candidature.
     */
    public function refuser(Request $request, CandidatureBenevole $candidature): RedirectResponse
    {
        $this->authorizeStaff($candidature->poste);

        $request->validate([
            'motif_refus' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $candidature->update([
            'statut'        => CandidatureBenevole::STATUT_REFUSE,
            'motif_refus'   => $request->motif_refus,
            'valide_par_id' => Auth::id(),
            'valide_le'     => now(),
        ]);

        return back()->with('success', 'Candidature refusée. Le bénévole a été notifié avec le motif.');
    }



    private function authorizeStaff(PosteBenevole $poste): void
    {
        $user = Auth::user();

        // Admin + Responsable : tout pouvoir
        if ($user->hasAnyRole(['admin', 'responsable_dcirp'])) {
            return;
        }

        // Organisateur : seulement SES événements
        if ($user->hasRole('organisateur') && $poste->evenement->created_by === $user->id) {
            return;
        }

        abort(403, 'Vous ne pouvez gérer que les bénévoles de vos propres événements.');
    }
}
