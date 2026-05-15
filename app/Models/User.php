<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'nom',
        'prenom',
        'email',
        'password',
        'telephone',
        'is_active',
        'tentatives_connexion',
        'bloque_jusqu_a',
    ];

    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'is_active'           => 'boolean',
            'bloque_jusqu_a'      => 'datetime',
        ];
    }

     function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'nom',
                'prenom',
                'email',
                'telephone',
                'is_active',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(
                fn(string $eventName): string => "Utilisateur {$eventName}"
            );
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function tachesResponsables(): HasMany
    {
        return $this->hasMany(Tache::class, 'responsable_id');
    }

    public function projets(): HasMany
    {
        return $this->hasMany(Projet::class);
    }

    public function evaluationsJury(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'jury_id');
    }

    public function equipeMembres(): HasMany
    {
        return $this->hasMany(EquipeMembre::class);
    }

    public function benevoleAffectations(): HasMany
    {
        return $this->hasMany(BenevoleAffectation::class);
    }

    public function intervenantsSessions(): HasMany
    {
        return $this->hasMany(IntervenantSession::class);
    }

    public function dotations(): HasMany
    {
        return $this->hasMany(Dotation::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function b2bMeetings(): HasMany
    {
        return $this->hasMany(B2BMeeting::class, 'prospect_id');
    }

    public function meetingsOrganised(): HasMany
    {
        return $this->hasMany(B2BMeeting::class, 'organisateur_id');
    }

    public function reponsesEnquetes(): HasMany
    {
        return $this->hasMany(ReponseEnquete::class);
    }

    public function presencesScannees(): HasMany
    {
        return $this->hasMany(Presence::class, 'scane_par');
    }

    public function estBloque(): bool
    {
        if (!$this->bloque_jusqu_a) {
            return false;
        }

        if (now()->isAfter($this->bloque_jusqu_a)) {

            // Déblocage automatique
            $this->update([
                'tentatives_connexion' => 0,
                'bloque_jusqu_a' => null,
            ]);

            return false;
        }

        return true;
    }

    public function incrementerTentatives(): void
    {
        $this->tentatives_connexion += 1;

        if ($this->tentatives_connexion >= 3) {
            $this->bloque_jusqu_a = now()->addMinutes(15);
        }

        $this->save();
    }
    public function reinitialiserTentatives(): void
    {
        $this->update([
            'tentatives_connexion' => 0,
            'bloque_jusqu_a' => null,
        ]);
    }

    public function inscriptionsPreselectionnees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Inscription::class, 'presele_par_id');
    }

    public function inscriptionsRecommandees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Inscription::class, 'recommande_par_id');
    }
    public function inscriptionsValidees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Inscription::class, 'valide_par_id');
    }
}