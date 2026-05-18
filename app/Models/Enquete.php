<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquete extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'titre',
        'type',
        'questions',
        'statut',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    // ─── RELATIONS ──────────────────────
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseEnquete::class);
    }

    // ─── SCOPES ─────────────────────────
    public function scopeBrouillon(Builder $q): Builder
    {
        return $q->where('statut', 'brouillon');
    }

    public function scopePublie(Builder $q): Builder
    {
        return $q->where('statut', 'publie');
    }

    public function scopeCloture(Builder $q): Builder
    {
        return $q->where('statut', 'cloture');
    }

    // ─── HELPERS ────────────────────────
    public function getNbQuestionsAttribute(): int
    {
        return count($this->questions['items'] ?? []);
    }

    public function getNbReponsesAttribute(): int
    {
        return $this->reponses()->count();
    }

    public function getEstOuverteAttribute(): bool
    {
        return $this->statut === 'publie';
    }

    public function getEstClotureeAttribute(): bool
    {
        return $this->statut === 'cloture';
    }

    /**
     * Vérifie si un utilisateur a déjà répondu.
     */
    public function aDejaRepondu(int $userId): bool
    {
        return $this->reponses()->where('user_id', $userId)->exists();
    }

    /**
     * Liste les types de questions supportés.
     */
    public static function typesQuestions(): array
    {
        return [
            'note'           => 'Note (étoiles)',
            'choix_unique'   => 'Choix unique (radio)',
            'choix_multiple' => 'Choix multiples (cases)',
            'texte_court'    => 'Texte court',
            'texte_long'     => 'Texte long',
            'oui_non'        => 'Oui / Non',
        ];
    }
}