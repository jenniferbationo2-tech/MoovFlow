<?php

namespace App\Http\Controllers;

use App\Models\MootNotification;
use App\Models\User;
use App\Services\OmnichannelNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly OmnichannelNotificationService $notificationService
    ) {
    }

    /**
     * Liste les notifications système avec filtres et pagination.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('communication.view'), 403);

        $filters = [
            'canal' => $request->string('canal')->toString(),
            'statut' => $request->string('statut')->toString(),
        ];

        $notifications = MootNotification::query()
            ->with('user:id,name,email,telephone')
            ->when($filters['canal'] !== '', fn ($builder) => $builder->where('canal', $filters['canal']))
            ->when($filters['statut'] !== '', fn ($builder) => $builder->where('statut', $filters['statut']))
            ->latest('date_envoi')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (MootNotification $notification): array => [
                'id' => $notification->id,
                'titre' => $notification->titre,
                'message' => $notification->message,
                'canal' => $notification->canal,
                'statut' => $notification->statut,
                'date_envoi' => optional($notification->date_envoi)?->toIso8601String(),
                'destinataire' => [
                    'id' => $notification->user?->id,
                    'name' => $notification->user?->name,
                    'email' => $notification->user?->email,
                    'telephone' => $notification->user?->telephone,
                ],
            ]);

        return Inertia::render('Communication/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $filters,
            'canaux' => ['email', 'sms', 'push'],
            'statuts' => ['envoye', 'echoue', 'en_attente'],
            'users' => User::query()->orderBy('name')->limit(100)->get(['id', 'name', 'email', 'telephone']),
        ]);
    }

    /**
     * Envoie une notification manuelle à un utilisateur.
     */
    public function send(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('communication.send'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'titre' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'canal' => ['nullable', Rule::in(['email', 'sms', 'push'])],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        $this->notificationService->send(
            $user,
            $validated['titre'],
            $validated['message'],
            $validated['canal'] ?? null,
        );

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notification envoyée avec succès.');
    }

    /**
     * Affiche les préférences de notification actuellement appliquées.
     */
    public function settings(Request $request): Response
    {
        abort_unless($request->user()?->can('communication.manage'), 403);

        $user = $request->user();

        return Inertia::render('Communication/Notifications/Settings', [
            'preferences' => [
                'canal_defaut' => (string) data_get($user, 'device_token', '') !== ''
                    ? 'push'
                    : ((string) ($user?->telephone ?? '') !== '' ? 'sms' : 'email'),
                'email_disponible' => (bool) $user?->email,
                'sms_disponible' => (bool) $user?->telephone,
                'push_disponible' => (string) data_get($user, 'device_token', '') !== '',
            ],
            'canaux' => ['email', 'sms', 'push'],
        ]);
    }
}