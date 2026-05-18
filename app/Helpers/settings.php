<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Récupère un paramètre système.
     *
     * Usage:
     *   setting('app_name')              → retourne la valeur ou null
     *   setting('app_name', 'MoovFlow')  → retourne la valeur ou "MoovFlow"
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}