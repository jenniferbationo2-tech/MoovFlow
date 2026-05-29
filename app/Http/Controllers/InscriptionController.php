<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\EmailLog;
use App\Services\InscriptionEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InscriptionController extends Controller
{
    public function __construct(
        private readonly InscriptionEmailService $emailService,
    ) {}

    

    public function index(Request $request): InertiaResponse
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403,
            'Accès réservé au staff.'
        );

        $query = Inscription::query()
            ->with([
                'user:id,nom,prenom,email,telephone',
                'evenement:id,titre,date_debut,type_evenement_id,created_by',
                'evenement.typeEvenement:id,nom,code',
            ]);

        // Si organisateur (pas admin/responsable) : voir seulement SES événements
        if ($user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp'])) {
            $query->whereHas('evenement', fn ($q) => $q->where('created_by', $user->id));
        }

        // Filtre statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre événement
        if ($request->filled('evenement_id')) {
            $query->where('evenement_id', $request->evenement_id);
        }

        // Filtre niveau (niveau_1 = pré-inscriptions, niveau_2 = dossiers)
        if ($request->filled('niveau')) {
            $query->where('niveau_inscription', $request->niveau);
        }

        // Recherche texte
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->whereHas('user', function ($u) use ($q) {
                    $u->where('nom', 'like', "%$q%")
                      ->orWhere('prenom', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                });
            });
        }

        $inscriptions = $query->latest()->paginate(20);

        // KPIs
        $kpis = $this->calculerKpis($user);

        // Liste des événements pour le filtre
        $evenements = Evenement::query()
            ->when(
                $user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp']),
                fn ($q) => $q->where('created_by', $user->id)
            )
            ->orderBy('titre')
            ->get(['id', 'titre']);

        return Inertia::render('Inscriptions/Index', [
            'inscriptions' => $inscriptions,
            'kpis'         => $kpis,
            'evenements'   => $evenements,
            'filters'      => $request->only(['statut', 'evenement_id', 'niveau', 'search']),
            'userRole'     => [
                'estResponsable'  => $user->hasAnyRole(['admin', 'responsable_dcirp']),
                'estOrganisateur' => $user->hasRole('organisateur'),
            ],
        ]);
    }

    private function calculerKpis($user): array
    {
        $query = Inscription::query();

        if ($user->hasRole('organisateur') && !$user->hasAnyRole(['admin', 'responsable_dcirp'])) {
            $query->whereHas('evenement', fn ($q) => $q->where('created_by', $user->id));
        }

        return [
            'total'           => (clone $query)->count(),
            'preinscrits'     => (clone $query)->where('statut', Inscription::STATUT_PREINSCRIT)->count(),
            'a_analyser'      => (clone $query)->whereIn('statut', [
                Inscription::STATUT_DOSSIER_SOUMIS,
                Inscription::STATUT_EN_ANALYSE,
            ])->count(),
            'recommandees'    => (clone $query)->where('statut', Inscription::STATUT_RECOMMANDEE)->count(),
            'acceptees'       => (clone $query)->whereIn('statut', [
                Inscription::STATUT_ACCEPTEE,
                Inscription::STATUT_CONFIRMEE,
            ])->count(),
            'refusees'        => (clone $query)->where('statut', Inscription::STATUT_REFUSEE)->count(),
        ];
    }

   

    public function create(Evenement $evenement): InertiaResponse|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'Connectez-vous pour vous pré-inscrire.');
        }

        $evenement->load(['typeEvenement', 'lieu']);

        // Bloquer SALON
        if ($evenement->typeEvenement?->code === 'SALON') {
            return redirect()->route('evenements.show', $evenement)
                ->with('info', 'Le Salon est une vitrine. Aucune pré-inscription nécessaire.');
        }

        // Vérifier que l'événement est publié
        if (!in_array($evenement->statut, ['publie', 'en_cours'])) {
            return redirect()->route('evenements.show', $evenement)
                ->with('error', 'Cet événement n\'est pas ouvert aux inscriptions.');
        }

        // Vérifier que l'utilisateur ne s'est pas déjà inscrit
        $existante = Inscription::where('evenement_id', $evenement->id)
            ->where('user_id', Auth::id())
            ->whereNotIn('statut', [Inscription::STATUT_REFUSEE, Inscription::STATUT_ANNULEE])
            ->first();

        if ($existante) {
            return redirect()->route('inscriptions.show', $existante)
                ->with('info', 'Vous êtes déjà inscrit à cet événement.');
        }

        return Inertia::render('Inscriptions/Preinscription', [
            'evenement' => $evenement,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'evenement_id'       => ['required', 'exists:evenements,id'],
            'motivation_courte'  => ['required', 'string', 'min:20', 'max:500'],
            'reglement_accepte'  => ['accepted'],
        ]);

        $evenement = Evenement::with('typeEvenement')->findOrFail($validated['evenement_id']);

        DB::beginTransaction();

        try {
            $inscription = Inscription::create([
                'evenement_id'         => $evenement->id,
                'user_id'              => Auth::id(),
                'motivation'           => $validated['motivation_courte'],
                'statut'               => Inscription::STATUT_PREINSCRIT,
                'niveau_inscription'   => Inscription::NIVEAU_1,
                'qr_code'              => 'MOOV-' . strtoupper(Str::random(8)),
            ]);

            $inscription->load(['user', 'evenement.typeEvenement', 'evenement.lieu']);

          
            try {
                $this->emailService->envoyer($inscription, EmailLog::TYPE_PREINSCRIPTION_RECUE);
            } catch (\Exception $e) {
                \Log::warning("Email pré-inscription non envoyé (inscription_id={$inscription->id}) : " . $e->getMessage());
                // On continue, l'inscription est valide
            }

            DB::commit();

            return redirect()->route('inscriptions.show', $inscription)
                ->with('success', 'Votre pré-inscription a été soumise avec succès ! Un email de confirmation vous a été envoyé.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la pré-inscription : ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════
    //   NIVEAU 2 : DOSSIER COMPLET
    // ════════════════════════════════════════

    public function dossierComplet(Request $request, Inscription $inscription): InertiaResponse|RedirectResponse
    {
        // Vérifier le token sécurisé OU être le propriétaire
        $token = $request->query('token');
        $isOwner = Auth::check() && $inscription->user_id === Auth::id();
        $tokenValide = $token && $inscription->token_acces === $token && $inscription->tokenEstValide();

        abort_unless($isOwner || $tokenValide, 403, 'Accès non autorisé à ce dossier.');

        // Vérifier le statut
        if (!in_array($inscription->statut, [
            Inscription::STATUT_PRESELECTIONNE,
            Inscription::STATUT_DOSSIER_SOUMIS,
        ])) {
            return redirect()->route('inscriptions.show', $inscription)
                ->with('info', 'Ce dossier ne peut plus être modifié.');
        }

        $inscription->load(['evenement.typeEvenement', 'evenement.lieu', 'user']);

        return Inertia::render('Inscriptions/DossierComplet', [
            'inscription' => $inscription,
            'evenement'   => $inscription->evenement,
            'typeCode'    => $inscription->evenement->typeEvenement?->code,
        ]);
    }

    public function soumettreDossier(Request $request, Inscription $inscription): RedirectResponse
    {
        // Mêmes contrôles que dossierComplet
        $token = $request->input('token');
        $isOwner = Auth::check() && $inscription->user_id === Auth::id();
        $tokenValide = $token && $inscription->token_acces === $token && $inscription->tokenEstValide();

        abort_unless($isOwner || $tokenValide, 403);

        $typeCode = $inscription->evenement->typeEvenement?->code;

        // Validation selon le type
        $rules = $this->reglesValidationDossier($typeCode);
        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            $inscription->update(array_merge($validated, [
                'statut' => Inscription::STATUT_DOSSIER_SOUMIS,
                'token_acces' => null, // Invalider le token
                'token_expire_at' => null,
            ]));

            $this->emailService->envoyer($inscription, EmailLog::TYPE_DOSSIER_RECU);

            DB::commit();

            return redirect()->route('inscriptions.show', $inscription)
                ->with('success', 'Votre dossier complet a été soumis ! Vous recevrez une réponse sous peu.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════
    //   ACTIONS STAFF : PRÉSÉLECTION
    // ════════════════════════════════════════

    public function preselectionner(Inscription $inscription): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']), 403);
        abort_unless($inscription->peutEtrePreselectionne(), 422, 'Cette inscription ne peut pas être présélectionnée.');

        DB::beginTransaction();

        try {
            $token = $inscription->genererTokenAcces(7);

            $inscription->update([
                'statut'         => Inscription::STATUT_PRESELECTIONNE,
                'presele_par_id' => $user->id,
                'presele_le'     => now(),
                'niveau_inscription' => Inscription::NIVEAU_2,
            ]);

            // Lien sécurisé vers le dossier complet
            $lien = route('inscriptions.dossier-complet', $inscription) . '?token=' . $token;

            $this->emailService->envoyer($inscription, EmailLog::TYPE_PRESELECTIONNE, [
                'lien_dossier' => $lien,
            ]);

            DB::commit();

            return back()->with('success', 'Candidat présélectionné ! Un email avec le lien vers le dossier complet a été envoyé.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════
    //   ACTIONS STAFF : RECOMMANDATION (Organisateur)
    // ════════════════════════════════════════

    public function recommander(Request $request, Inscription $inscription): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']), 403);
        abort_unless($inscription->peutEtreRecommandee(), 422, 'Cette inscription ne peut pas être recommandée.');

        $request->validate([
            'note_organisateur' => ['nullable', 'string', 'max:1000'],
        ]);

        $inscription->update([
            'statut'            => Inscription::STATUT_RECOMMANDEE,
            'recommande_par_id' => $user->id,
            'recommande_le'     => now(),
            'note_organisateur' => $request->note_organisateur,
        ]);

        return back()->with('success', 'Candidature recommandée. En attente de validation finale par le responsable dCIRP.');
    }

    // ════════════════════════════════════════
    //   ACTIONS STAFF : VALIDATION FINALE (Responsable)
    // ════════════════════════════════════════

    public function valider(Inscription $inscription): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'responsable_dcirp']), 403, 'Seul le responsable dCIRP peut valider.');
        abort_unless($inscription->peutEtreValidee(), 422, 'Cette inscription ne peut pas être validée.');

        DB::beginTransaction();

        try {
            // S'assurer d'un QR code unique
            if (!$inscription->qr_code) {
                $inscription->qr_code = 'MOOV-' . strtoupper(Str::random(8));
            }

            $inscription->update([
                'statut'        => Inscription::STATUT_CONFIRMEE,
                'valide_par_id' => $user->id,
                'valide_le'     => now(),
            ]);

            $this->emailService->envoyer($inscription, EmailLog::TYPE_ACCEPTE);

            DB::commit();

            return back()->with('success', 'Candidature acceptée ! Email + QR code envoyés au candidat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════
    //   ACTIONS STAFF : REFUS
    // ════════════════════════════════════════

    public function refuser(Request $request, Inscription $inscription): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']), 403);
        abort_unless($inscription->peutEtreRefusee(), 422, 'Cette inscription ne peut pas être refusée.');

        $request->validate([
            'motif_refus' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::beginTransaction();

        try {
            $inscription->update([
                'statut'      => Inscription::STATUT_REFUSEE,
                'motif_refus' => $request->motif_refus,
            ]);

            $this->emailService->envoyer($inscription, EmailLog::TYPE_REFUSE, [
                'motif' => $request->motif_refus,
            ]);

            DB::commit();

            return back()->with('success', 'Candidature refusée. Le candidat a été notifié.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════
    //   VOIR UNE INSCRIPTION
    // ════════════════════════════════════════

    public function show(Inscription $inscription): InertiaResponse
    {
        $user = Auth::user();

        // Sécurité : seul le proprio ou staff peut voir
        $isOwner = $user && $inscription->user_id === $user->id;
        $isStaff = $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']);

        abort_unless($isOwner || $isStaff, 403);

       $inscription->load([
            'user:id,nom,prenom,email,telephone',
            'evenement.typeEvenement',
            'evenement.lieu',
            'preseleParUser:id,nom,prenom',
            'recommandeParUser:id,nom,prenom',
            'valideParUser:id,nom,prenom',
            'emailLogs',
            'dossier',        
            'tarif',           
            'paiement',       
        ]);
      
        if ($isStaff && !$isOwner) {
            return Inertia::render('Inscriptions/Detail', [
                'inscription' => $inscription,
                'userRole'    => [
                    'estResponsable'  => $user->hasAnyRole(['admin', 'responsable_dcirp']),
                    'estOrganisateur' => $user->hasRole('organisateur'),
                ],
            ]);
        }

        // Le participant voit la version Show (sa propre vue)
        return Inertia::render('Inscriptions/Show', [
            'inscription' => $inscription,
        ]);
    }

   
    public function mesInscriptions(Request $request): InertiaResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $query = Inscription::query()
            ->with(['evenement.typeEvenement', 'evenement.lieu'])
            ->where('user_id', $user->id);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $inscriptions = $query->latest()->get();

        return Inertia::render('Inscriptions/MesInscriptions', [
            'inscriptions' => $inscriptions,
            'filters'      => $request->only(['statut']),
        ]);
    }

    public function annuler(Inscription $inscription): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $inscription->user_id === $user->id, 403);

        if (!in_array($inscription->statut, [
            Inscription::STATUT_PREINSCRIT,
            Inscription::STATUT_PRESELECTIONNE,
            Inscription::STATUT_DOSSIER_SOUMIS,
        ])) {
            return back()->with('error', 'Cette inscription ne peut plus être annulée.');
        }

        $inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        return back()->with('success', 'Inscription annulée.');
    }


    private function reglesValidationDossier(?string $typeCode): array
    {
        $base = [
            'motivation'  => ['nullable', 'string', 'max:2000'],
            'token'       => ['nullable', 'string'],
        ];

        return match ($typeCode) {
            'BARA_MOUSSO' => array_merge($base, [
                'localite'           => ['required', 'string', 'max:100'],
                'domaine_activite'   => ['required', 'string', 'max:100'],
                'effectif_employe'   => ['required', 'string', 'max:50'],
                'annee_creation'     => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
                'url_video_pitch'    => ['nullable', 'url', 'max:500'],
                'besoins_financiers' => ['nullable', 'numeric', 'min:0'],
                'description_projet' => ['required', 'string', 'min:50', 'max:3000'],
            ]),
            'SPORT' => array_merge($base, [
                'nom_equipe'        => ['required', 'string', 'max:200'],
                'capitaine'         => ['required', 'string', 'max:200'],
                'categorie_age'     => ['required', 'string', 'max:50'],
                'effectif_equipe'   => ['required', 'integer', 'min:1', 'max:30'],
                'couleurs_maillot'  => ['nullable', 'string', 'max:100'],
                'coach_nom'         => ['nullable', 'string', 'max:200'],
                'joueurs_licencies' => ['nullable', 'integer', 'min:0'],
            ]),
            'HACK' => array_merge($base, [
                'nom_equipe'        => ['required', 'string', 'max:200'],
                'niveau_equipe'     => ['required', 'string', 'max:50'],
                'technologies'      => ['nullable', 'string', 'max:1000'],
                'presence_complete' => ['boolean'],
                'url_portfolio'     => ['nullable', 'url', 'max:500'],
                'idee'              => ['required', 'string', 'min:30'],
            ]),
            'CHALLENGE' => array_merge($base, [
                'titre_idee'           => ['required', 'string', 'max:300'],
                'description'          => ['required', 'string', 'min:100'],
                'stade_maturite'       => ['required', 'string', 'max:50'],
                'marche_vise'          => ['required', 'string', 'max:50'],
                'investissement_requis'=> ['nullable', 'numeric', 'min:0'],
                'statut_juridique'     => ['required', 'string', 'max:100'],
            ]),
            default => $base,
        };
    }

    
    public function exportParticipantsPdf(\App\Models\Evenement $evenement, \App\Services\ParticipantsPdfService $pdfService)
    {
        $user = Auth::user();

        // Seul le staff peut télécharger
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403,
            'Accès non autorisé.'
        );

        return $pdfService->genererPdf($evenement);
    }
}