<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'nom', 'prenom', 'email', 'password', 'telephone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Configure la journalisation d activite sur les colonnes sensibles.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly(['name', 'nom', 'prenom', 'email', 'telephone', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Utilisateur {$eventName}");
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
}
