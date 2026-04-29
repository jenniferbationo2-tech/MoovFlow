<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retourne les parametres sous forme cle => valeur.
     *
     * @return array<string, string|null>
     */
    public static function keyed(): array
    {
        return static::query()
            ->orderBy('key')
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Enregistre plusieurs parametres.
     *
     * @param  array<string, string|null>  $values
     */
    public static function persistMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}