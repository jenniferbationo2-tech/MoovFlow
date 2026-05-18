<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Cache des paramètres pour éviter des requêtes répétées.
     */
    private const CACHE_KEY = 'app.settings';
    private const CACHE_TTL = 3600; // 1 heure

    /**
     * Retourne les paramètres sous forme clé => valeur.
     */
    public static function keyed(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::query()
                ->orderBy('key')
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    /**
     * Récupère un paramètre par sa clé avec fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::keyed();
        return $settings[$key] ?? $default;
    }

    /**
     * Enregistre plusieurs paramètres.
     */
    public static function persistMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Enregistre un seul paramètre.
     */
    public static function set(string $key, mixed $value): void
    {
        self::persistMany([$key => $value]);
    }

    /**
     * Vide le cache des paramètres.
     */
    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}