<?php

namespace App\Http\Controllers;

use App\Services\NotificationsAggregatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationBarreController extends Controller
{
    public function __construct(
        private readonly NotificationsAggregatorService $notifications,
    ) {
    }

    /**
     * Marque les notifications actuelles de la cloche navbar comme vues,
     * pour que leur badge ne réapparaisse pas tant que rien n'a changé.
     */
    public function marquerVues(): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $role = $this->notifications->resolvePrimaryRole($user);
        $this->notifications->marquerVues($user, $role);

        return back();
    }
}
