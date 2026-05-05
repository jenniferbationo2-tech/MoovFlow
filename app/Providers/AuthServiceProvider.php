<?php

namespace App\Providers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Policies\EvenementPolicy;
use App\Policies\InscriptionPolicy;
use App\Policies\RapportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Les mappings policies => modeles.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Evenement::class => EvenementPolicy::class,
        Inscription::class => InscriptionPolicy::class,
    ];

    /**
     * Enregistre les policies et capacites metier.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('rapports.viewAny', [RapportPolicy::class, 'viewAny']);
        Gate::define('rapports.export', [RapportPolicy::class, 'export']);
        Gate::define('manage-users', fn ($user) => $user->hasRole('admin'));
    }
}