<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Route;

class AuthenticatedSessionController extends Controller
{
    /**
     * Affiche la page de connexion.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Authentifie l'utilisateur avec gestion du blocage 3 tentatives (DS1).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 1️⃣ — Chercher l'utilisateur
        $user = User::where('email', $request->email)->first();

        // 2️⃣ — Si email inconnu → message générique (sécurité)
        if (!$user) {
            return back()->withErrors([
                'email' => 'Identifiants incorrects.',
            ])->onlyInput('email');
        }

        // 3️⃣ — Compte bloqué ?
        if ($user->estBloque()) {
            $minutes = (int) now()->diffInMinutes($user->bloque_jusqu_a) + 1;
            return back()->withErrors([
                'email' => "Compte temporairement bloqué. Réessayez dans {$minutes} minute(s).",
            ])->onlyInput('email');
        }

        // 4️⃣ — Compte désactivé ?
        if (!$user->is_active) {
            return back()->withErrors([
                'email' => 'Votre compte est désactivé. Contactez l\'administrateur.',
            ])->onlyInput('email');
        }

        // 5️⃣ — Vérifier le mot de passe
        if (!Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ], $request->boolean('remember'))) {

            // Mot de passe incorrect → incrémenter
            $user->incrementerTentatives();
            $user->refresh();

            // Si vient d'être bloqué
            if ($user->estBloque()) {
                return back()->withErrors([
                    'email' => 'Compte bloqué pendant 15 minutes après 3 tentatives échouées.',
                ])->onlyInput('email');
            }

            $restantes = 3 - $user->tentatives_connexion;
            return back()->withErrors([
                'email' => "Identifiants incorrects. Il vous reste {$restantes} tentative(s).",
            ])->onlyInput('email');
        }

        // 6️⃣ — Connexion réussie → réinitialiser et régénérer la session
        $user->reinitialiserTentatives();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}