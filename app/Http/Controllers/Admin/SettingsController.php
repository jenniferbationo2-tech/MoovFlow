<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('admin.settings'), 403);

        $settings = Setting::keyed();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'app_name' => $settings['app_name'] ?? config('app.name'),
                'app_logo' => $settings['app_logo'] ?? null,
                'smtp_host' => $settings['smtp_host'] ?? config('mail.mailers.smtp.host'),
                'smtp_port' => $settings['smtp_port'] ?? (string) config('mail.mailers.smtp.port'),
                'smtp_username' => $settings['smtp_username'] ?? config('mail.mailers.smtp.username'),
                'payment_public_key' => $settings['payment_public_key'] ?? null,
                'payment_secret_key' => $settings['payment_secret_key'] ?? null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.settings'), 403);

        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'string', 'max:20'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'payment_public_key' => ['nullable', 'string', 'max:255'],
            'payment_secret_key' => ['nullable', 'string', 'max:255'],
        ]);

        Setting::persistMany($validated);

        return back()->with('success', 'Parametres sauvegardes avec succes.');
    }
}