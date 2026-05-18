<?php

namespace App\Http\Controllers;

use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\ReponseEnquete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EnqueteController extends Controller
{
  
    public function index(Evenement $evenement): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        $enquetes = $evenement->enquetes()
            ->withCount('reponses')
            ->latest()
            ->get()
            ->map(fn (Enquete $e) => [
                'id'           => $e->id,
                'titre'        => $e->titre,
                'type'         => $e->type,
                'statut'       => $e->statut,
                'nb_questions' => count($e->questions['items'] ?? []),
                'nb_reponses'  => $e->reponses_count,
                'created_at'   => $e->created_at->toIso8601String(),
            ]);

        return Inertia::render('Enquetes/Index', [
            'evenement' => [
                'id'         => $evenement->id,
                'titre'      => $evenement->titre,
                'date_debut' => $evenement->date_debut?->toIso8601String(),
                'date_fin'   => $evenement->date_fin?->toIso8601String(),
                'statut'     => $evenement->statut,
            ],
            'enquetes' => $enquetes,
        ]);
    }

    public function create(Evenement $evenement): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        return Inertia::render('Enquetes/Create', [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'typesQuestions' => Enquete::typesQuestions(),
        ]);
    }


    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        $validated = $request->validate([
            'titre'                  => ['required', 'string', 'max:255'],
            'type'                   => ['nullable', 'string', 'max:100'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.type'           => ['required', 'string', 'in:note,choix_unique,choix_multiple,texte_court,texte_long,oui_non'],
            'items.*.label'          => ['required', 'string', 'max:500'],
            'items.*.obligatoire'    => ['nullable', 'boolean'],
            'items.*.options'        => ['nullable', 'array'],
            'items.*.options.*'      => ['string', 'max:255'],
            'items.*.echelle'        => ['nullable', 'integer', 'min:2', 'max:10'],
        ]);

        // Construire le JSON propre des questions
        $items = collect($validated['items'])->map(function ($item, $index) {
            $clean = [
                'id'          => 'q' . ($index + 1),
                'type'        => $item['type'],
                'label'       => $item['label'],
                'obligatoire' => $item['obligatoire'] ?? false,
            ];

            if (in_array($item['type'], ['choix_unique', 'choix_multiple'])) {
                $clean['options'] = array_values(array_filter($item['options'] ?? [], fn ($o) => trim($o) !== ''));
            }

            if ($item['type'] === 'note') {
                $clean['echelle'] = $item['echelle'] ?? 5;
            }

            return $clean;
        })->toArray();

        $enquete = Enquete::create([
            'evenement_id' => $evenement->id,
            'titre'        => $validated['titre'],
            'type'         => $validated['type'] ?? 'satisfaction',
            'questions'    => ['version' => '1.0', 'items' => $items],
            'statut'       => 'brouillon',
        ]);

        return redirect()
            ->route('communication.enquetes.show', [$evenement, $enquete])
            ->with('success', 'Enquête créée en brouillon. Pensez à la publier.');
    }

    public function show(Evenement $evenement, Enquete $enquete): Response
    {
        $user = Auth::user();

        // 2 cas : organisateur/admin OU participant
        $estOrganisateurOuAdmin = $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);

        // Données communes
        $data = [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'enquete' => [
                'id'        => $enquete->id,
                'titre'     => $enquete->titre,
                'type'      => $enquete->type,
                'statut'    => $enquete->statut,
                'questions' => $enquete->questions,
            ],
            'isManager'      => $estOrganisateurOuAdmin,
            'aDejaRepondu'   => $enquete->aDejaRepondu($user->id),
        ];

        // Pour les managers, on ajoute les statistiques
        if ($estOrganisateurOuAdmin) {
            $data['stats'] = [
                'nb_reponses' => $enquete->reponses()->count(),
            ];
        }

        return Inertia::render('Enquetes/Show', $data);
    }

  
    public function respond(Request $request, Enquete $enquete): RedirectResponse
    {
        // Seules les enquêtes publiées sont répondables
        if ($enquete->statut !== 'publie') {
            return back()->withErrors(['enquete' => 'Cette enquête n\'est pas ouverte aux réponses.']);
        }

        $user = Auth::user();

        // Pas de double réponse
        if ($enquete->aDejaRepondu($user->id)) {
            return back()->withErrors(['enquete' => 'Vous avez déjà répondu à cette enquête.']);
        }

        $validated = $request->validate([
            'reponses' => ['required', 'array'],
        ]);

        // Vérifier les questions obligatoires
        $items = $enquete->questions['items'] ?? [];
        foreach ($items as $item) {
            if (($item['obligatoire'] ?? false) && empty($validated['reponses'][$item['id']])) {
                return back()->withErrors([
                    'reponses' => "La question \"{$item['label']}\" est obligatoire.",
                ]);
            }
        }

        ReponseEnquete::create([
            'enquete_id' => $enquete->id,
            'user_id'    => $user->id,
            'reponses'   => $validated['reponses'],
        ]);

        return redirect()
            ->route('communication.enquetes.show', [$enquete->evenement_id, $enquete])
            ->with('success', 'Merci pour votre retour ! Votre réponse a bien été enregistrée.');
    }

    public function publish(Enquete $enquete): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($enquete->evenement);

        if ($enquete->statut !== 'brouillon') {
            return back()->withErrors(['statut' => 'Seules les enquêtes en brouillon peuvent être publiées.']);
        }

        $enquete->update(['statut' => 'publie']);

        return back()->with('success', 'Enquête publiée. Les participants peuvent maintenant y répondre.');
    }

    
    public function close(Enquete $enquete): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($enquete->evenement);

        if ($enquete->statut !== 'publie') {
            return back()->withErrors(['statut' => 'Seules les enquêtes publiées peuvent être clôturées.']);
        }

        $enquete->update(['statut' => 'cloture']);

        return back()->with('success', 'Enquête clôturée. Plus de nouvelles réponses possibles.');
    }

    
    private function autoriserOrganisateurOuAdmin(Evenement $evenement): void
    {
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['admin', 'responsable_dcirp']) ||
                ($user->hasRole('organisateur') && $evenement->created_by === $user->id),
            403,
            'Vous n\'avez pas accès à cet événement.'
        );
    }
}