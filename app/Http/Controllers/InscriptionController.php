<?php

namespace App\Http\Controllers;

use App\Exports\EvenementParticipantsExport;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\Tarif;
use App\Models\User;
use App\Services\PaiementService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InscriptionController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly PaiementService $paiementService
    ) {
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Inscription::class);

        $filters = [
            'search' => $request->string('search')->toString(),
            'evenement' => $request->string('evenement')->toString(),
            'statut' => $request->string('statut')->toString(),
        ];

        $query = Inscription::query()
            ->with(['user', 'evenement', 'tarif', 'paiement.facture'])
            ->when($filters['evenement'] !== '', fn ($builder) => $builder->where('evenement_id', $filters['evenement']))
            ->when($filters['statut'] !== '', fn ($builder) => $builder->where('statut', $filters['statut']))
            ->when($filters['search'] !== '', function ($builder) use ($filters): void {
                $builder->whereHas('user', function ($userQuery) use ($filters): void {
                    $userQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%');
                });
            });

        $statsQuery = clone $query;
        $total = (clone $statsQuery)->count();
        $payes = (clone $statsQuery)->whereHas('paiement', fn ($builder) => $builder->where('statut', 'paye'))->count();
        $attente = (clone $statsQuery)->where('statut', 'attente')->count();

        $inscriptions = $query
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Inscription $inscription): array => $this->mapInscriptionListItem($inscription));

        return Inertia::render('Inscriptions/Index', [
            'inscriptions' => $inscriptions,
            'filters' => $filters,
            'evenements' => Evenement::query()->orderBy('titre')->get(['id', 'titre']),
            'statuts' => [
                ['value' => 'attente', 'label' => 'En attente'],
                ['value' => 'valide', 'label' => 'Validee'],
                ['value' => 'annule', 'label' => 'Annulee'],
            ],
            'stats' => [
                'total' => $total,
                'payes' => $payes,
                'attente' => $attente,
                'conversion' => $total > 0 ? round(($payes / $total) * 100, 1) : 0,
            ],
        ]);
    }

    public function create(Evenement $evenement): Response
    {
        Gate::authorize('create', Inscription::class);

        $evenement->loadMissing(['tarifs', 'lieu.salles']);

        return Inertia::render('Inscriptions/Create', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'description' => $evenement->description,
                'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
                'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
            ],
            'tarifs' => $evenement->tarifs->map(fn (Tarif $tarif): array => [
                'id' => $tarif->id,
                'nom' => $tarif->nom,
                'montant' => (float) $tarif->montant,
            ])->values()->all(),
            'places' => $this->placesPayload($evenement),
            'participant' => [
                'name' => request()->user()?->name,
                'email' => request()->user()?->email,
                'telephone' => request()->user()?->telephone,
            ],
            'modesPaiement' => [
                ['value' => 'moov_money', 'label' => 'Moov Money'],
                ['value' => 'cash', 'label' => 'Cash'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Inscription::class);

        $validated = $request->validate([
            'evenement_id' => ['required', 'exists:evenements,id'],
            'tarif_id' => ['nullable', 'exists:tarifs,id'],
            'participant_name' => ['nullable', 'string', 'max:255'],
            'participant_email' => ['nullable', 'email', 'max:255'],
            'participant_telephone' => ['nullable', 'string', 'max:50'],
            'mode' => ['nullable', Rule::in(['cash', 'moov_money', 'gratuit'])],
        ]);

        $evenement = Evenement::query()->with(['tarifs', 'lieu.salles'])->findOrFail($validated['evenement_id']);
        $tarif = $validated['tarif_id'] ? Tarif::query()->findOrFail($validated['tarif_id']) : null;

        if ($tarif && (int) $tarif->evenement_id !== (int) $evenement->id) {
            return back()->withErrors([
                'tarif_id' => 'Le tarif selectionne ne correspond pas a cet evenement.',
            ])->withInput();
        }

        $places = $this->placesPayload($evenement);

        if ($places['available'] !== null && $places['available'] <= 0) {
            return back()->withErrors([
                'evenement_id' => 'Le quota de places disponibles est atteint.',
            ])->withInput();
        }

        $participant = $this->resolveParticipant($request, $validated);

        $duplicate = Inscription::query()
            ->where('user_id', $participant->id)
            ->where('evenement_id', $evenement->id)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'participant_email' => 'Ce participant est deja inscrit a cet evenement.',
            ])->withInput();
        }

        $inscription = null;

        DB::transaction(function () use (&$inscription, $participant, $evenement, $tarif, $validated): void {
            $inscription = Inscription::query()->create([
                'user_id' => $participant->id,
                'evenement_id' => $evenement->id,
                'tarif_id' => $tarif?->id,
                'statut' => (float) ($tarif?->montant ?? 0) > 0 ? 'attente' : 'valide',
                'qr_code' => Str::upper((string) Str::ulid()),
            ]);

            $this->qrCodeService->generate($inscription);
            $this->paiementService->initierPaiement(
                $inscription,
                (float) ($tarif?->montant ?? 0) > 0 ? ($validated['mode'] ?? 'moov_money') : 'gratuit'
            );
        });

        return redirect()
            ->route('inscriptions.show', $inscription)
            ->with('success', 'Inscription enregistree avec succes.');
    }

    public function show(Inscription $inscription): Response
    {
        $this->authorize('view', $inscription);

        $inscription->load([
            'user',
            'evenement',
            'tarif',
            'paiement.facture',
            'paiements',
            'presence.scanneur',
        ]);

        return Inertia::render('Inscriptions/Show', [
            'inscription' => $this->mapInscriptionDetail($inscription),
        ]);
    }

    public function destroy(Inscription $inscription): RedirectResponse
    {
        $this->authorize('delete', $inscription);

        $inscription->update([
            'statut' => 'annule',
        ]);

        if ($inscription->paiement && $inscription->paiement->statut === 'initie') {
            $inscription->paiement->update([
                'statut' => 'echec',
            ]);
        }

        return redirect()
            ->route('inscriptions.index')
            ->with('success', 'Inscription annulee avec succes.');
    }

    public function export(Evenement $evenement): BinaryFileResponse
    {
        Gate::authorize('export', Inscription::class);

        return Excel::download(
            new EvenementParticipantsExport($evenement),
            'participants-evenement-'.$evenement->id.'.xlsx'
        );
    }

    private function resolveParticipant(Request $request, array $validated): User
    {
        Role::findOrCreate('participant');

        $email = $validated['participant_email'] ?? null;

        if ($email) {
            $participant = User::query()->firstOrNew([
                'email' => $email,
            ]);

            $participant->fill([
                'name' => $validated['participant_name'] ?: ($participant->name ?: $email),
                'telephone' => $validated['participant_telephone'] ?? $participant->telephone,
            ]);

            if (! $participant->exists) {
                $participant->password = Str::password(16);
            }

            $participant->save();
        } else {
            $participant = $request->user();

            if ($participant && ($validated['participant_telephone'] ?? null)) {
                $participant->update([
                    'telephone' => $validated['participant_telephone'],
                ]);
            }
        }

        if (! $participant->hasRole('participant')) {
            $participant->assignRole('participant');
        }

        return $participant;
    }

    private function placesPayload(Evenement $evenement): array
    {
        $capacite = $evenement->lieu?->salles?->sum('capacite');
        $inscrits = $evenement->inscriptions()->where('statut', '!=', 'annule')->count();

        return [
            'capacity' => $capacite > 0 ? $capacite : null,
            'registered' => $inscrits,
            'available' => $capacite > 0 ? max($capacite - $inscrits, 0) : null,
        ];
    }

    private function mapInscriptionListItem(Inscription $inscription): array
    {
        return [
            'id' => $inscription->id,
            'participant' => [
                'name' => $inscription->user?->name,
                'email' => $inscription->user?->email,
            ],
            'evenement' => [
                'id' => $inscription->evenement?->id,
                'titre' => $inscription->evenement?->titre,
            ],
            'tarif' => [
                'nom' => $inscription->tarif?->nom ?? 'Gratuit',
                'montant' => (float) ($inscription->tarif?->montant ?? 0),
            ],
            'statut' => $inscription->statut,
            'paiement_statut' => $inscription->paiement?->statut ?? 'gratuit',
            'qr_code_url' => $this->qrCodeService->publicUrl($inscription->qr_code),
            'date_inscription' => optional($inscription->created_at)?->toIso8601String(),
        ];
    }

    private function mapInscriptionDetail(Inscription $inscription): array
    {
        return [
            'id' => $inscription->id,
            'statut' => $inscription->statut,
            'qr_code' => $inscription->qr_code,
            'qr_code_url' => $this->qrCodeService->publicUrl($inscription->qr_code),
            'date_inscription' => optional($inscription->created_at)?->toIso8601String(),
            'participant' => [
                'id' => $inscription->user?->id,
                'name' => $inscription->user?->name,
                'email' => $inscription->user?->email,
                'telephone' => $inscription->user?->telephone,
            ],
            'evenement' => [
                'id' => $inscription->evenement?->id,
                'titre' => $inscription->evenement?->titre,
                'date_debut' => optional($inscription->evenement?->date_debut)?->toIso8601String(),
                'date_fin' => optional($inscription->evenement?->date_fin)?->toIso8601String(),
            ],
            'tarif' => [
                'nom' => $inscription->tarif?->nom ?? 'Gratuit',
                'montant' => (float) ($inscription->tarif?->montant ?? 0),
            ],
            'paiement' => $inscription->paiement ? [
                'id' => $inscription->paiement->id,
                'montant' => (float) $inscription->paiement->montant,
                'mode' => $inscription->paiement->mode,
                'statut' => $inscription->paiement->statut,
                'reference' => $inscription->paiement->reference_transaction,
                'facture_url' => $this->storageUrl($inscription->paiement->facture?->url_pdf),
                'show_url' => route('paiements.show', $inscription->paiement),
            ] : null,
            'presence' => [
                'statut' => $inscription->presence ? 'present' : 'absent',
                'scan_time' => optional($inscription->presence?->scan_time)?->toIso8601String(),
                'scanneur' => $inscription->presence?->scanneur?->name,
            ],
        ];
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::url($path);
    }
}