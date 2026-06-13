<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Récupérer l'utilisateur pour le compteur de tentatives
        $user = User::where('email', $this->string('email')->toString())->first();

        // Vérifier si le compte est verrouillé par notre système (pas le rate limiter Laravel)
        if ($user && $user->compte_verrouille) {
            throw ValidationException::withMessages([
                'email' => 'Votre compte est verrouillé après plusieurs tentatives échouées. Contactez l\'administrateur.',
            ]);
        }

        // Tentative de connexion
        if (! Auth::attempt([
            'email'     => $this->string('email')->toString(),
            'password'  => $this->string('password')->toString(),
            'is_active' => true,
        ], $this->boolean('remember'))) {

            RateLimiter::hit($this->throttleKey());

            // Incrémenter le compteur de tentatives
            if ($user) {
                $user->tentatives_connexion = ($user->tentatives_connexion ?? 0) + 1;

                if ($user->tentatives_connexion >= 3) {
                    $user->compte_verrouille = true;
                    $user->save();

                    throw ValidationException::withMessages([
                        'email' => 'Compte verrouillé après 3 tentatives échouées. Contactez l\'administrateur.',
                    ]);
                }

                $restantes = 3 - $user->tentatives_connexion;
                $user->save();

                throw ValidationException::withMessages([
                    'email' => "Erreur de connexion. Il vous reste {$restantes} tentative(s), veuillez réessayer.",
                ]);
            }

            // Email inconnu → message générique (sécurité)
            throw ValidationException::withMessages([
                'email' => 'Erreur de connexion. Vérifiez vos identifiants.',
            ]);
        }

        // Connexion réussie → on remet les tentatives à zéro
        if ($user) {
            $user->tentatives_connexion = 0;
            $user->save();
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Trop de tentatives. Réessayez dans " . ceil($seconds / 60) . " minute(s).",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}