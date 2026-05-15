<?php

namespace App\Http\Controllers;

use App\Models\DossierInscription;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\Tarif;
use App\Models\User;
use App\Services\PaiementService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;


class InscriptionController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly PaiementService $paiementService,
    ) {}

    /**
     * Liste globale des dossiers d'inscription (pour staff).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Inscription::class);

        $user = Auth::user();

        $query = Inscription::query()
            ->with([
                'user:id,nom,prenom,email,telephone',
                'evenement:id,titre,type_evenement_id,created_by',
                'evenement.typeEvenement:id,nom,code',
                'tarif:id,nom,montant',
                'dossier',
                'analysePar:id,nom,prenom',
            ]);

        if ($user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp'])) {
            $query->whereHas('evenement', fn($q) => $q->where('created_by', $user->id));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('evenement_id')) {
            $query->where('evenement_id', $request->evenement_id);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('user', fn($u) => $u->where('nom', 'like', "%$q%")
                    ->orWhere('prenom', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%"))
                    ->orWhere('qr_code', 'like', "%$q%");
            });
        }

        $inscriptions = $query->latest()->paginate(20)->through(fn(Inscription $i) => [
            'id'          => $i->id,
            'qr_code'     => $i->qr_code,
            'statut'      => $i->statut,
            'motif_refus' => $i->motif_refus,
            'created_at'  => optional($i->created_at)?->toIso8601String(),
            'date_analyse' => optional($i->date_analyse)?->toIso8601String(),
            'user' => $i->user ? [
                'id'        => $i->user->id,
                'nom'       => $i->user->nom,
                'prenom'    => $i->user->prenom,
                'email'     => $i->user->email,
                'telephone' => $i->user->telephone,
            ] : null,
            'evenement' => $i->evenement ? [
                'id'    => $i->evenement->id,
                'titre' => $i->evenement->titre,
                'type'  => $i->evenement->typeEvenement ? [
                    'nom'  => $i->evenement->typeEvenement->nom,
                    'code' => $i->evenement->typeEvenement->code,
                ] : null,
            ] : null,
            'tarif' => $i->tarif ? [
                'libelle' => $i->tarif->nom,
                'montant' => (float) $i->tarif->montant,
                'devise'  => 'FCFA',
            ] : null,
            'dossier' => $i->dossier?->toArray(),
            'analyse_par' => $i->analysePar ? [
                'nom'    => $i->analysePar->nom,
                'prenom' => $i->analysePar->prenom,
            ] : null,
        ]);

        $statsQuery = Inscription::query();
        if ($user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp'])) {
            $statsQuery->whereHas('evenement', fn($q) => $q->where('created_by', $user->id));
        }

        $stats = [
            'total'      => (clone $statsQuery)->count(),
            'en_attente' => (clone $statsQuery)->where('statut', 'en_attente')->count(),
            'en_analyse' => (clone $statsQuery)->where('statut', 'en_analyse')->count(),
            'acceptee'   => (clone $statsQuery)->where('statut', 'acceptee')->count(),
            'confirmee'  => (clone $statsQuery)->where('statut', 'confirmee')->count(),
            'refusee'    => (clone $statsQuery)->where('statut', 'refusee')->count(),
        ];

        $evenements = Evenement::query()
            ->select('id', 'titre')
            ->when(
                $user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp']),
                fn($q) => $q->where('created_by', $user->id)
            )
            ->orderBy('titre')
            ->get();

        return Inertia::render('Inscriptions/Index', [
            'inscriptions' => $inscriptions,
            'stats'        => $stats,
            'evenements'   => $evenements,
            'filters'      => $request->only(['statut', 'evenement_id', 'search']),
        ]);
    }

    /**
     * Affiche le formulaire de préinscription adapté au type d'événement.
     */
    public function create(Evenement $evenement): RedirectResponse|Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'Connectez-vous pour vous inscrire à cet événement.');
        }

        $evenement->load(['typeEvenement', 'lieu', 'tarifs']);

        if ($evenement->typeEvenement?->code === 'SALON') {
            return redirect()->route('evenements.show', $evenement)
                ->with('info', 'Cet événement est organisé par un tiers. Visitez le stand Moov sur place.');
        }

        if ($evenement->statut !== 'publie') {
            return redirect()->route('evenements.show', $evenement)
                ->with('error', 'Cet événement n\'est pas ouvert aux inscriptions.');
        }

        $dejaInscrit = Inscription::where('evenement_id', $evenement->id)
            ->where('user_id', Auth::id())
            ->whereNotIn('statut', ['refusee', 'annulee'])
            ->exists();

        if ($dejaInscrit) {
            return redirect()->route('evenements.show', $evenement)
                ->with('error', 'Vous avez déjà soumis un dossier pour cet événement.');
        }

        return Inertia::render('Inscriptions/Create', [
            'evenement' => [
                'id'           => $evenement->id,
                'titre'        => $evenement->titre,
                'description'  => $evenement->description,
                'date_debut'   => optional($evenement->date_debut)?->toIso8601String(),
                'date_fin'     => optional($evenement->date_fin)?->toIso8601String(),
                'lieu'         => $evenement->lieu ? ['nom' => $evenement->lieu->nom] : null,
                'reglement_pdf_url' => $evenement->reglement_pdf
                    ? asset('storage/' . $evenement->reglement_pdf)
                    : null,
                'type_evenement' => $evenement->typeEvenement ? [
                    'id'   => $evenement->typeEvenement->id,
                    'nom'  => $evenement->typeEvenement->nom,
                    'code' => $evenement->typeEvenement->code,
                ] : null,
                'tarifs' => $evenement->tarifs->map(fn($t) => [
                    'id'      => $t->id,
                    'libelle' => $t->nom,
                    'montant' => (float) $t->montant,
                    'devise'  => 'FCFA',
                ])->all(),
            ],
        ]);
    }


    public function show(Inscription $inscription): Response
    {
        Gate::authorize('view', $inscription);

        $inscription->load([
            'user',
            'evenement.typeEvenement',
            'evenement.lieu',
            'tarif',
            'dossier',
            'analysePar',
            'paiement',
        ]);

        $user = Auth::user();
        $estStaff = $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);

        $payload = [
            'id'          => $inscription->id,
            'qr_code'     => $inscription->qr_code,
            'statut'      => $inscription->statut,
            'motif_refus' => $inscription->motif_refus,
            'created_at'  => optional($inscription->created_at)?->toIso8601String(),
            'date_analyse' => optional($inscription->date_analyse)?->toIso8601String(),
            'user' => $inscription->user ? [
                'id'        => $inscription->user->id,
                'nom'       => $inscription->user->nom,
                'prenom'    => $inscription->user->prenom,
                'email'     => $inscription->user->email,
                'telephone' => $inscription->user->telephone,
            ] : null,
            'evenement' => $inscription->evenement ? [
                'id'         => $inscription->evenement->id,
                'titre'      => $inscription->evenement->titre,
                'date_debut' => optional($inscription->evenement->date_debut)?->toIso8601String(),
                'lieu'       => $inscription->evenement->lieu ? [
                    'nom' => $inscription->evenement->lieu->nom,
                ] : null,
                'type'       => $inscription->evenement->typeEvenement ? [
                    'nom'  => $inscription->evenement->typeEvenement->nom,
                    'code' => $inscription->evenement->typeEvenement->code,
                ] : null,
            ] : null,
            'tarif' => $inscription->tarif ? [
                'libelle' => $inscription->tarif->libelle,
                'montant' => (float) $inscription->tarif->montant,
                'devise'  => $inscription->tarif->devise ?? 'XOF',
            ] : null,
            'dossier' => $inscription->dossier ? array_merge(
                $inscription->dossier->toArray(),
                [
                    'fichier_joint_url' => $inscription->dossier->fichier_joint
                        ? asset('storage/' . $inscription->dossier->fichier_joint)
                        : null,
                ]
            ) : null,
            'analyse_par' => $inscription->analysePar ? [
                'nom'    => $inscription->analysePar->nom,
                'prenom' => $inscription->analysePar->prenom,
            ] : null,
            'paiement' => $inscription->paiement,
        ];

        // Vue staff vs participant
        return Inertia::render(
            $estStaff ? 'Inscriptions/Detail' : 'Inscriptions/Show',
            ['inscription' => $payload]
        );
    }

    /**
     * Crée une nouvelle préinscription.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Inscription::class);

        $validated = $request->validate([
            'evenement_id'           => ['required', 'exists:evenements,id'],
            'tarif_id'               => ['nullable', 'exists:tarifs,id'],
            'reglement_accepte'      => ['nullable', 'boolean'],
            'mode'                   => ['nullable', Rule::in(['cash', 'moov_money', 'gratuit'])],
            // Champs du dossier
            'organisation'           => ['nullable', 'string', 'max:255'],
            'fonction'               => ['nullable', 'string', 'max:255'],
            'motivation'             => ['nullable', 'string', 'max:1000'],
            'fichier_joint'          => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            // Bara Mousso
            'nom_association'        => ['nullable', 'string', 'max:255'],
            'description_projet'     => ['nullable', 'string'],
            'nb_membres_association' => ['nullable', 'integer', 'min:1'],
            'budget_projet'          => ['nullable', 'numeric', 'min:0'],
            // Sport
            'nom_equipe'             => ['nullable', 'string', 'max:255'],
            'nb_joueurs'             => ['nullable', 'integer', 'min:1'],
            'categorie_equipe'       => ['nullable', 'string', 'max:50'],
            'responsable_equipe'     => ['nullable', 'string', 'max:255'],
            // Hackathon
            'competences_techniques' => ['nullable', 'string', 'max:255'],
            'stack_technologique'    => ['nullable', 'string', 'max:255'],
            'nom_equipe_hack'        => ['nullable', 'string', 'max:255'],
            'nb_membres_equipe'      => ['nullable', 'integer', 'min:1'],
            // Formation
            'niveau_formation'       => ['nullable', Rule::in(['debutant', 'intermediaire', 'avance'])],
            'objectifs_apprentissage' => ['nullable', 'string'],
            // Salon
            'secteur_activite'       => ['nullable', 'string', 'max:255'],
            'type_visite_salon'      => ['nullable', Rule::in(['visiteur', 'partenaire_potentiel', 'client_potentiel'])],
            'interets_b2b'           => ['nullable', 'string'],
            // Challenge
            'titre_idee'             => ['nullable', 'string', 'max:255'],
            'secteur_idee'           => ['nullable', 'string', 'max:255'],
        ]);

        $evenement = Evenement::query()
            ->with(['tarifs', 'lieu.salles', 'typeEvenement'])
            ->findOrFail($validated['evenement_id']);

        // Vérification du règlement
        if ($evenement->reglement_pdf && !($validated['reglement_accepte'] ?? false)) {
            return back()->withErrors([
                'reglement_accepte' => 'Vous devez accepter le règlement de l\'événement pour soumettre votre dossier.',
            ])->withInput();
        }

        $tarif = $validated['tarif_id'] ? Tarif::findOrFail($validated['tarif_id']) : null;

        if ($tarif && (int) $tarif->evenement_id !== (int) $evenement->id) {
            return back()->withErrors([
                'tarif_id' => 'Le tarif sélectionné ne correspond pas à cet événement.',
            ])->withInput();
        }

        $participant = Auth::user();

        $duplicate = Inscription::query()
            ->where('user_id', $participant->id)
            ->where('evenement_id', $evenement->id)
            ->whereNotIn('statut', ['refusee', 'annulee'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'evenement_id' => 'Vous avez déjà un dossier en cours pour cet événement.',
            ])->withInput();
        }

        $inscription = null;

        DB::transaction(function () use (
            &$inscription,
            $participant,
            $evenement,
            $tarif,
            $validated,
            $request
        ): void {

            $inscription = Inscription::query()->create([
                'user_id'      => $participant->id,
                'evenement_id' => $evenement->id,
                'tarif_id'     => $tarif?->id,
                'statut'       => 'en_attente',
                'qr_code'      => Str::upper((string) Str::ulid()),
            ]);

            $dossierData = [
                'inscription_id' => $inscription->id,
                'organisation'   => $request->input('organisation'),
                'fonction'       => $request->input('fonction'),
                'motivation'     => $request->input('motivation'),
            ];

            if ($request->hasFile('fichier_joint')) {
                $dossierData['fichier_joint'] = $request->file('fichier_joint')
                    ->store('dossiers', 'public');
            }

            $typeCode = $evenement->typeEvenement?->code;

            switch ($typeCode) {
                case 'BARA_MOUSSO':
                    $dossierData['nom_association']        = $request->input('nom_association');
                    $dossierData['description_projet']     = $request->input('description_projet');
                    $dossierData['nb_membres_association'] = $request->input('nb_membres_association');
                    $dossierData['budget_projet']          = $request->input('budget_projet');
                    break;
                case 'HACK':
                    $dossierData['competences_techniques'] = $request->input('competences_techniques');
                    $dossierData['stack_technologique']    = $request->input('stack_technologique');
                    $dossierData['nom_equipe_hack']        = $request->input('nom_equipe_hack');
                    $dossierData['nb_membres_equipe']      = $request->input('nb_membres_equipe');
                    break;
                case 'FORMATION':
                    $dossierData['niveau_formation']        = $request->input('niveau_formation');
                    $dossierData['objectifs_apprentissage'] = $request->input('objectifs_apprentissage');
                    break;
                case 'SPORT':
                    $dossierData['nom_equipe']         = $request->input('nom_equipe');
                    $dossierData['nb_joueurs']         = $request->input('nb_joueurs');
                    $dossierData['categorie_equipe']   = $request->input('categorie_equipe');
                    $dossierData['responsable_equipe'] = $request->input('responsable_equipe');
                    break;
                case 'SALON':
                    $dossierData['secteur_activite']  = $request->input('secteur_activite');
                    $dossierData['type_visite_salon'] = $request->input('type_visite_salon');
                    $dossierData['interets_b2b']      = $request->input('interets_b2b');
                    break;
                case 'CHALLENGE':
                    $dossierData['titre_idee']   = $request->input('titre_idee');
                    $dossierData['secteur_idee'] = $request->input('secteur_idee');
                    break;
            }

            DossierInscription::create($dossierData);

            $this->qrCodeService->generate($inscription);

            if ($tarif && (float) $tarif->montant > 0) {
                $this->paiementService->initierPaiement(
                    $inscription,
                    $validated['mode'] ?? 'moov_money'
                );
            }
        });

        return redirect()
            ->route('inscriptions.show', $inscription)
            ->with('success', 'Dossier soumis avec succès. Vous serez notifié après analyse par l\'organisateur.');
    }

    // ════════════════════════════════════════════════
    //   WORKFLOW PRÉINSCRIPTION
    // ════════════════════════════════════════════════

    public function analyser(Inscription $inscription): RedirectResponse
    {
        Gate::authorize('update', $inscription);

        if ($inscription->statut !== 'en_attente') {
            return back()->with(
                'error',
                "Ce dossier ne peut pas être analysé (statut : {$inscription->statut})."
            );
        }

        $inscription->update([
            'statut'       => 'en_analyse',
            'date_analyse' => now(),
            'analyse_par'  => Auth::id(),
        ]);

        return back()->with('success', 'Dossier marqué en cours d\'analyse.');
    }

    public function accepter(Inscription $inscription): RedirectResponse
    {
        Gate::authorize('update', $inscription);

        if (!in_array($inscription->statut, ['en_attente', 'en_analyse'])) {
            return back()->with(
                'error',
                "Ce dossier ne peut pas être accepté (statut : {$inscription->statut})."
            );
        }

        $tarif = $inscription->tarif;
        $estGratuit = !$tarif || (float) $tarif->montant <= 0;

        $inscription->update([
            'statut'       => $estGratuit ? 'confirmee' : 'acceptee',
            'date_analyse' => now(),
            'analyse_par'  => Auth::id(),
        ]);

        if ($estGratuit) {
            $this->qrCodeService->generate($inscription);
        }

        return back()->with(
            'success',
            $estGratuit
                ? 'Dossier accepté et confirmé. QR code généré.'
                : 'Dossier accepté. En attente de paiement.'
        );
    }

    public function refuser(Request $request, Inscription $inscription): RedirectResponse
    {
        Gate::authorize('update', $inscription);

        $request->validate([
            'motif_refus' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'motif_refus.required' => 'Le motif de refus est obligatoire.',
            'motif_refus.min'      => 'Le motif doit comporter au moins 10 caractères.',
        ]);

        if (!in_array($inscription->statut, ['en_attente', 'en_analyse'])) {
            return back()->with(
                'error',
                "Ce dossier ne peut pas être refusé (statut : {$inscription->statut})."
            );
        }

        $inscription->update([
            'statut'       => 'refusee',
            'motif_refus'  => $request->motif_refus,
            'date_analyse' => now(),
            'analyse_par'  => Auth::id(),
        ]);

        return back()->with('success', 'Dossier refusé. Le participant sera notifié.');
    }

    /**
     * Liste les inscriptions de l'utilisateur connecté (vue participant).
     */
    public function mesInscriptions(Request $request): Response
    {
        $user = Auth::user();

        $query = Inscription::query()
            ->where('user_id', $user->id)
            ->with([
                'evenement:id,titre,visuel,date_debut,date_fin,type_evenement_id,statut',
                'evenement.typeEvenement:id,nom,code',
                'evenement.lieu:id,nom',
                'tarif:id,nom,montant',
                'paiement',
            ]);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $inscriptions = $query->latest()->paginate(12)->through(fn(Inscription $i) => [
            'id'          => $i->id,
            'qr_code'     => $i->qr_code,
            'statut'      => $i->statut,
            'motif_refus' => $i->motif_refus,
            'created_at'  => optional($i->created_at)?->toIso8601String(),
            'date_analyse' => optional($i->date_analyse)?->toIso8601String(),
            'evenement' => $i->evenement ? [
                'id'         => $i->evenement->id,
                'titre'      => $i->evenement->titre,
                'visuel_url' => $i->evenement->visuel
                    ? asset('storage/' . $i->evenement->visuel)
                    : null,
                'date_debut' => optional($i->evenement->date_debut)?->toIso8601String(),
                'statut'     => $i->evenement->statut,
                'lieu'       => $i->evenement->lieu ? ['nom' => $i->evenement->lieu->nom] : null,
                'type'       => $i->evenement->typeEvenement ? [
                    'nom'  => $i->evenement->typeEvenement->nom,
                    'code' => $i->evenement->typeEvenement->code,
                ] : null,
            ] : null,
            'tarif' => $i->tarif ? [
                'libelle' => $i->tarif->nom,
                'montant' => (float) $i->tarif->montant,
                'devise'  => 'FCFA',
            ] : null,
            'paiement' => $i->paiement ? [
                'statut' => $i->paiement->statut,
            ] : null,
        ]);

        $stats = [
            'total'      => Inscription::where('user_id', $user->id)->count(),
            'en_attente' => Inscription::where('user_id', $user->id)
                ->whereIn('statut', ['en_attente', 'en_analyse'])->count(),
            'acceptees'  => Inscription::where('user_id', $user->id)
                ->whereIn('statut', ['acceptee', 'confirmee', 'present'])->count(),
            'refusees'   => Inscription::where('user_id', $user->id)
                ->where('statut', 'refusee')->count(),
        ];

        return Inertia::render('Inscriptions/MesInscriptions', [
            'inscriptions' => $inscriptions,
            'stats'        => $stats,
            'filters'      => $request->only(['statut']),
        ]);
    }

    /**
     * Annuler une inscription (par le participant lui-même).
     */
    public function annuler(Inscription $inscription): RedirectResponse
    {
        abort_unless($inscription->user_id === Auth::id(), 403);

        if (!in_array($inscription->statut, ['en_attente', 'en_analyse', 'acceptee'])) {
            return back()->with(
                'error',
                "Impossible d'annuler ce dossier (statut : {$inscription->statut})."
            );
        }

        $inscription->update(['statut' => 'annulee']);

        return back()->with('success', 'Votre dossier a été annulé.');
    }
}
