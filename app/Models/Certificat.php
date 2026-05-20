<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Certificat extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'evenement_id',
        'user_id',
        'fichier_path',
        'envoye_email',
        'envoye_at',
        'genere_at',
    ];

    protected $casts = [
        'envoye_email' => 'boolean',
        'envoye_at'    => 'datetime',
        'genere_at'    => 'datetime',
    ];

    // ─── RELATIONS ──────────────────────
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── HELPERS ────────────────────────
    
    /**
     * URL publique du PDF pour téléchargement.
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->fichier_path) return null;
        return Storage::disk('public')->url($this->fichier_path);
    }

    /**
     * Vérifie si le fichier PDF existe physiquement.
     */
    public function fichierExiste(): bool
    {
        return Storage::disk('public')->exists($this->fichier_path);
    }

    /**
     * Génère un nouveau numéro unique : CERT-2026-0001
     */
    public static function genererNumero(): string
    {
        $annee = now()->year;
        $derniereNumeroPourAnnee = static::where('numero', 'like', "CERT-{$annee}-%")
            ->orderByDesc('id')
            ->first();

        if (!$derniereNumeroPourAnnee) {
            $sequence = 1;
        } else {
            // Extraire le nombre après "CERT-2026-"
            $parts = explode('-', $derniereNumeroPourAnnee->numero);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('CERT-%d-%04d', $annee, $sequence);
    }
}