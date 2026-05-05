<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            $query->whereHas('roles', fn ($r) => $r->where('name', $request->role));
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

        $users = $query->latest()->paginate(15)->through(fn (User $u): array => [
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

        return back()->with('success',
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
}