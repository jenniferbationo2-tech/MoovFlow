<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Evenement;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-users');

        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%$q%")
                    ->orWhere('prenom', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('telephone', 'like', "%$q%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($r) => $r->where('name', $request->role));
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'actif') {
                $query->where('is_active', true)->whereNull('bloque_jusqu_a');
            } elseif ($request->statut === 'bloque') {
                $query->whereNotNull('bloque_jusqu_a')
                    ->where('bloque_jusqu_a', '>', now());
            } elseif ($request->statut === 'desactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest()->paginate(15)->through(fn(User $u): array => [
            'id'                   => $u->id,
            'nom'                  => $u->nom,
            'prenom'               => $u->prenom,
            'email'                => $u->email,
            'telephone'            => $u->telephone,
            'is_active'            => (bool) $u->is_active,
            'bloque_jusqu_a'       => optional($u->bloque_jusqu_a)?->toIso8601String(),
            'tentatives_connexion' => $u->tentatives_connexion ?? 0,
            'created_at'           => optional($u->created_at)?->toIso8601String(),
            'last_login_at'        => optional($u->last_login_at ?? null)?->toIso8601String(),
            'roles'                => $u->roles->pluck('name')->all(),
        ]);

        $stats = [
            'total'             => User::count(),
            'admin'             => User::role('admin')->count(),
            'responsable_dcirp' => User::role('responsable_dcirp')->count(),
            'organisateur'      => User::role('organisateur')->count(),
            'participant'       => User::role('participant')->count(),
            'inactifs'          => User::where('is_active', false)->count(),
        ];

        $rolesDisponibles = Role::orderBy('name')->pluck('name')->all();

        return Inertia::render('Admin/Users/Index', [
            'users'            => $users,
            'stats'            => $stats,
            'rolesDisponibles' => $rolesDisponibles,
            'filters'          => $request->only(['search', 'role', 'statut']),
        ]);
    }

    public function create()
    {
        return inertia('Admin/Users/Create');
    }

public function edit(User $user)
{
    return inertia('Admin/Users/Edit', [
        'user' => $user
    ]);
}



    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'prenom'    => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'password'  => ['required', 'string', 'min:8'],
            'role'      => ['required', Rule::in(['admin', 'responsable_dcirp', 'organisateur', 'participant'])],
        ]);

        $user = User::create([
            'name'      => $validated['prenom'] . ' ' . $validated['nom'],
            'nom'       => $validated['nom'],
            'prenom'    => $validated['prenom'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', "Utilisateur {$user->prenom} {$user->nom} créé avec succès.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'prenom'    => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'telephone' => ['nullable', 'string', 'max:50'],
            'role'      => ['required', Rule::in(['admin', 'responsable_dcirp', 'organisateur', 'participant'])],
        ]);

        $user->update([
            'name'      => $validated['prenom'] . ' ' . $validated['nom'],
            'nom'       => $validated['nom'],
            'prenom'    => $validated['prenom'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'Utilisateur mis à jour.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update(['is_active' => !$user->is_active]);

        return back()->with(
            'success',
            $user->is_active
                ? "Compte {$user->prenom} {$user->nom} activé."
                : "Compte {$user->prenom} {$user->nom} désactivé."
        );
    }

    public function debloquer(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update([
            'bloque_jusqu_a'       => null,
            'tentatives_connexion' => 0,
        ]);

        return back()->with('success', "Compte de {$user->prenom} {$user->nom} débloqué.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', "Mot de passe de {$user->prenom} {$user->nom} réinitialisé.");
    }


    public function show(User $user): Response
    {
        Gate::authorize('manage-users');

        // Charger les rôles
        $user->load('roles');

        $activitesRecentes = Activity::where(function ($q) use ($user) {
            $q->where(function ($sub) use ($user) {
                $sub->where('subject_type', User::class)
                    ->where('subject_id', $user->id);
            })->orWhere('causer_id', $user->id);
        })
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'log_name'         => $a->log_name,
                'event'            => $a->event,
                'description'      => $a->description,
                'subject_type'     => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id'       => $a->subject_id,
                'created_at'       => $a->created_at->toIso8601String(),
                'created_at_human' => $a->created_at->locale('fr')->diffForHumans(),
                'is_causer'        => $a->causer_id === $user->id,
            ]);

        $connexions = Activity::where('causer_id', $user->id)
            ->where('log_name', 'request')
            ->where(function ($q) {
                $q->where('properties->url', 'like', '%/login%')
                    ->orWhere('properties->route', 'like', '%login%');
            })
            ->latest()
            ->take(15)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'created_at' => $a->created_at->toIso8601String(),
                'created_at_human' => $a->created_at->locale('fr')->diffForHumans(),
                'ip'         => $a->properties['ip'] ?? '—',
                'method'     => strtoupper($a->properties['method'] ?? '—'),
                'status'     => $a->properties['status'] ?? null,
                'url'        => $a->properties['url'] ?? '—',
                'reussie'    => in_array($a->properties['status'] ?? 0, [200, 302]),
            ]);

        // ─── ÉVÉNEMENTS CRÉÉS ───
        $evenementsCrees = Evenement::where('created_by', $user->id)
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->withCount('inscriptions')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($e) => [
                'id'                 => $e->id,
                'titre'              => $e->titre,
                'statut'             => $e->statut,
                'date_debut'         => $e->date_debut?->toIso8601String(),
                'type_nom'           => $e->typeEvenement?->nom,
                'type_code'          => $e->typeEvenement?->code,
                'lieu_nom'           => $e->lieu?->nom,
                'inscriptions_count' => $e->inscriptions_count,
            ]);

        $kpis = [
            'total_activites' => Activity::where(function ($q) use ($user) {
                $q->where('causer_id', $user->id)
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('subject_type', User::class)
                            ->where('subject_id', $user->id);
                    });
            })->count(),

            'total_connexions' => Activity::where('causer_id', $user->id)
                ->where('log_name', 'request')
                ->where(function ($q) {
                    $q->where('properties->url', 'like', '%/login%')
                        ->orWhere('properties->route', 'like', '%login%');
                })
                ->count(),

            'connexions_echec' => Activity::where('causer_id', $user->id)
                ->where('log_name', 'request')
                ->where(function ($q) {
                    $q->where('properties->url', 'like', '%/login%')
                        ->orWhere('properties->route', 'like', '%login%');
                })
                ->whereNotIn('properties->status', [200, 302])
                ->count(),

            'evenements_crees' => Evenement::where('created_by', $user->id)->count(),

            'jours_anciennete' => $user->created_at
                ? Carbon::parse($user->created_at)->diffInDays(now())
                : 0,
        ];

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id'                   => $user->id,
                'nom'                  => $user->nom,
                'prenom'               => $user->prenom,
                'email'                => $user->email,
                'telephone'            => $user->telephone,
                'is_active'            => (bool) $user->is_active,
                'bloque_jusqu_a'       => $user->bloque_jusqu_a?->toIso8601String(),
                'tentatives_connexion' => $user->tentatives_connexion ?? 0,
                'created_at'           => $user->created_at?->toIso8601String(),
                'deleted_at'           => $user->deleted_at?->toIso8601String(),
                'roles'                => $user->roles->pluck('name')->all(),
            ],
            'activites'        => $activitesRecentes,
            'connexions'       => $connexions,
            'evenementsCrees'  => $evenementsCrees,
            'kpis'             => $kpis,
        ]);
    }


    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        // Sécurité : ne pas se supprimer soi-même
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'delete' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        // Sécurité : ne pas supprimer le dernier admin
        if ($user->hasRole('admin')) {
            $autresAdmins = User::role('admin')->where('id', '!=', $user->id)->count();
            if ($autresAdmins === 0) {
                return back()->withErrors([
                    'delete' => 'Impossible de supprimer le dernier administrateur.',
                ]);
            }
        }

        $nom = "{$user->prenom} {$user->nom}";
        $user->delete(); // soft delete

        return redirect()->route('admin.users.index')
            ->with('success', "Compte de {$nom} archivé. Les logs sont conservés.");
    }


    public function restore(int $id): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return back()->withErrors(['restore' => 'Cet utilisateur n\'est pas archivé.']);
        }

        $user->restore();

        return back()->with('success', "Compte de {$user->prenom} {$user->nom} restauré.");
    }
}
