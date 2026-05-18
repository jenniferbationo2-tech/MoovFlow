<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Affiche la page des paramètres système.
     */
    public function index(): Response
    {
        Gate::authorize('manage-users');

        $all = Setting::keyed();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                // ─── GÉNÉRAL ───
                'app_name'        => $all['app_name']        ?? 'MoovFlow',
                'app_slogan'      => $all['app_slogan']      ?? 'Portail dCIRP - Moov Africa Burkina',
                'app_logo'        => $all['app_logo']        ?? null,
                'pays'            => $all['pays']            ?? 'Burkina Faso',
                'fuseau_horaire'  => $all['fuseau_horaire']  ?? 'Africa/Ouagadougou',
                'devise'          => $all['devise']          ?? 'FCFA',
                'adresse_moov'    => $all['adresse_moov']    ?? 'Ouaga 2000, Burkina Faso',
                'telephone_moov'  => $all['telephone_moov']  ?? '+226 70 00 00 01',
                'email_contact'   => $all['email_contact']   ?? 'contact@moov.bf',

                // ─── SÉCURITÉ ───
                'security_max_attempts'    => (int) ($all['security_max_attempts'] ?? 3),
                'security_lockout_minutes' => (int) ($all['security_lockout_minutes'] ?? 30),
                'security_password_min'    => (int) ($all['security_password_min'] ?? 8),
                'security_2fa_enabled'     => filter_var($all['security_2fa_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),

                // ─── NOTIFICATIONS ───
                'mail_from_address'             => $all['mail_from_address'] ?? config('mail.from.address', 'no-reply@moov.bf'),
                'mail_from_name'                => $all['mail_from_name']    ?? 'MoovFlow',
                'notif_participants_enabled'    => filter_var($all['notif_participants_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notif_organisateurs_enabled'   => filter_var($all['notif_organisateurs_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'notif_blocage_compte_enabled'  => filter_var($all['notif_blocage_compte_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    /**
     * Met à jour la section "Général".
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'app_name'       => ['required', 'string', 'max:255'],
            'app_slogan'     => ['nullable', 'string', 'max:255'],
            'pays'           => ['nullable', 'string', 'max:100'],
            'fuseau_horaire' => ['nullable', 'string', 'max:100'],
            'devise'         => ['nullable', 'string', 'max:10'],
            'adresse_moov'   => ['nullable', 'string', 'max:500'],
            'telephone_moov' => ['nullable', 'string', 'max:50'],
            'email_contact'  => ['nullable', 'email', 'max:255'],
            'app_logo'       => ['nullable', 'file', 'image', 'max:2048'], // 2 Mo max
        ]);

        // Upload logo si fourni
        if ($request->hasFile('app_logo')) {
            // Supprimer l'ancien si existe
            $ancienLogo = Setting::get('app_logo');
            if ($ancienLogo && Storage::disk('public')->exists($ancienLogo)) {
                Storage::disk('public')->delete($ancienLogo);
            }

            $chemin = $request->file('app_logo')->store('logos', 'public');
            $validated['app_logo'] = $chemin;
        } else {
            unset($validated['app_logo']);
        }

        Setting::persistMany($validated);

        return back()->with('success', 'Paramètres généraux enregistrés.');
    }

    /**
     * Met à jour la section "Sécurité".
     */
    public function updateSecurity(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'security_max_attempts'    => ['required', 'integer', 'min:1', 'max:10'],
            'security_lockout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'security_password_min'    => ['required', 'integer', 'min:6', 'max:32'],
            'security_2fa_enabled'     => ['required', 'boolean'],
        ]);

        // Convertir booléens en string pour stockage
        $validated['security_2fa_enabled'] = $validated['security_2fa_enabled'] ? '1' : '0';

        Setting::persistMany($validated);

        return back()->with('success', 'Paramètres de sécurité enregistrés.');
    }

    /**
     * Met à jour la section "Notifications".
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'mail_from_address'            => ['required', 'email', 'max:255'],
            'mail_from_name'               => ['required', 'string', 'max:255'],
            'notif_participants_enabled'   => ['required', 'boolean'],
            'notif_organisateurs_enabled'  => ['required', 'boolean'],
            'notif_blocage_compte_enabled' => ['required', 'boolean'],
        ]);

        // Convertir booléens
        $validated['notif_participants_enabled']   = $validated['notif_participants_enabled'] ? '1' : '0';
        $validated['notif_organisateurs_enabled']  = $validated['notif_organisateurs_enabled'] ? '1' : '0';
        $validated['notif_blocage_compte_enabled'] = $validated['notif_blocage_compte_enabled'] ? '1' : '0';

        Setting::persistMany($validated);

        return back()->with('success', 'Paramètres de notifications enregistrés.');
    }
}