<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs avec filtres.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $query = User::query()
            ->with('roles:name')
            ->when($filters['search'] !== '', function ($builder) use ($filters): void {
                $builder->where(function ($userQuery) use ($filters): void {
                    $userQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('nom', 'like', '%'.$filters['search'].'%')
                        ->orWhere('prenom', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%')
                        ->orWhere('telephone', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['role'] !== '', fn ($builder) => $builder->role($filters['role']))
            ->when($filters['status'] !== '', fn ($builder) => $builder->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name');

        $users = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => $this->mapListItem($user));

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles,
            'stats' => [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'by_role' => $roles->map(fn (Role $role): array => [
                    'name' => $role->name,
                    'total' => $role->users()->count(),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Affiche le formulaire de creation.
     */
    public function create(Request $request): Response
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Enregistre un nouvel utilisateur et son role.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $validated = $request->validate([
            'nom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::query()->create([
            'name' => $this->resolveDisplayName($validated),
            'nom' => $validated['nom'] ?? null,
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur cree avec succes');
    }

    /**
     * Affiche le detail complet d un utilisateur.
     */
    public function show(Request $request, User $user): Response
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $user->load('roles:name');
        $activities = Activity::query()
            ->with('causer')
            ->where(function ($query) use ($user): void {
                $query
                    ->where('causer_type', User::class)
                    ->where('causer_id', $user->id)
                    ->orWhere(function ($nested) use ($user): void {
                        $nested
                            ->where('subject_type', User::class)
                            ->where('subject_id', $user->id);
                    });
            })
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (Activity $activity): array => $this->mapActivity($activity))
            ->all();

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'is_active' => (bool) $user->is_active,
                'created_at' => optional($user->created_at)?->toIso8601String(),
                'roles' => $user->getRoleNames()->values()->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                'activity' => $activities,
            ],
        ]);
    }

    /**
     * Affiche le formulaire d edition.
     */
    public function edit(Request $request, User $user): Response
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'is_active' => (bool) $user->is_active,
                'role' => $user->getRoleNames()->first(),
            ],
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Met a jour un utilisateur et ses roles.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $validated = $request->validate([
            'nom' => ['nullable', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'telephone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $this->resolveDisplayName($validated),
            'nom' => $validated['nom'] ?? null,
            'prenom' => $validated['prenom'] ?? null,
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $user->is_active),
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur mis a jour avec succes');
    }

    /**
     * Desactive logiquement un utilisateur.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $user->update(['is_active' => false]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur desactive avec succes');
    }

    /**
     * Active ou desactive un compte.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.users'), 403);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Statut utilisateur mis a jour');
    }

    /**
     * Formate un utilisateur pour la liste.
     *
     * @return array<string, mixed>
     */
    private function mapListItem(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'is_active' => (bool) $user->is_active,
            'roles' => $user->getRoleNames()->values()->all(),
            'created_at' => optional($user->created_at)?->toIso8601String(),
        ];
    }

    /**
     * Formate une entree du journal.
     *
     * @return array<string, mixed>
     */
    private function mapActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'created_at' => optional($activity->created_at)?->toIso8601String(),
            'causer' => $activity->causer ? [
                'id' => $activity->causer->id,
                'name' => $activity->causer->name,
            ] : null,
            'properties' => $activity->properties?->toArray() ?? [],
        ];
    }

    /**
     * Construit le nom d affichage a partir du formulaire.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveDisplayName(array $validated): string
    {
        $parts = array_filter([
            $validated['prenom'] ?? null,
            $validated['nom'] ?? null,
        ]);

        return count($parts) > 0 ? implode(' ', $parts) : (string) $validated['email'];
    }
}