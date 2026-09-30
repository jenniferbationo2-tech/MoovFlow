<?php

namespace App\Http\Controllers;

use App\Models\Certificat;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Services\CertificatPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificatController extends Controller
{
    
    public function index(Evenement $evenement): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        // Charger les présences (pour info)
        $totalPresents = Inscription::where('evenement_id', $evenement->id)
            ->where('statut', 'present')
            ->count();

        // Certificats déjà générés
        $certificats = Certificat::where('evenement_id', $evenement->id)
            ->with('user:id,prenom,nom,email')
            ->orderByDesc('genere_at')
            ->get()
            ->map(fn (Certificat $c) => [
                'id'            => $c->id,
                'numero'        => $c->numero,
                'user'          => [
                    'id'     => $c->user->id,
                    'prenom' => $c->user->prenom,
                    'nom'    => $c->user->nom,
                    'email'  => $c->user->email,
                ],
                'fichier_path'  => $c->fichier_path,
                'url'           => $c->url,
                'envoye_email'  => $c->envoye_email,
                'envoye_at'     => $c->envoye_at?->toIso8601String(),
                'genere_at'     => $c->genere_at->toIso8601String(),
                'fichier_exists' => $c->fichierExiste(),
            ]);

        $presentsAvecCertificat = $certificats->pluck('user.id')->toArray();
        $presentsSansCertificat = Inscription::where('evenement_id', $evenement->id)
            ->where('statut', 'present')
            ->whereNotIn('user_id', $presentsAvecCertificat)
            ->with('user:id,prenom,nom,email')
            ->get()
            ->map(fn (Inscription $i) => [
                'inscription_id' => $i->id,
                'user' => [
                    'id'     => $i->user->id,
                    'prenom' => $i->user->prenom,
                    'nom'    => $i->user->nom,
                    'email'  => $i->user->email,
                ],
            ]);

        return Inertia::render('Certificats/Index', [
            'evenement' => [
                'id'         => $evenement->id,
                'titre'      => $evenement->titre,
                'date_debut' => $evenement->date_debut?->toIso8601String(),
                'date_fin'   => $evenement->date_fin?->toIso8601String(),
                'statut'     => $evenement->statut,
            ],
            'kpis' => [
                'total_presents'           => $totalPresents,
                'certificats_generes'      => $certificats->count(),
                'certificats_a_generer'    => $presentsSansCertificat->count(),
                'certificats_envoyes'      => $certificats->where('envoye_email', true)->count(),
            ],
            'certificats'            => $certificats,
            'presentsSansCertificat' => $presentsSansCertificat,
        ]);
    }

    
    public function generer(Evenement $evenement, CertificatPdfService $service): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        $resume = $service->genererPourEvenement($evenement);

        $message = "Génération terminée : {$resume['generes']} créé(s)";
        if ($resume['existants'] > 0) {
            $message .= ", {$resume['existants']} déjà existant(s)";
        }
        if ($resume['erreurs'] > 0) {
            $message .= ", {$resume['erreurs']} erreur(s)";
        }
        $message .= '.';

        return back()->with('success', $message);
    }

    
    public function telecharger(Certificat $certificat): BinaryFileResponse|RedirectResponse
    {
        $certificat->load('evenement');
        $this->autoriserOrganisateurOuAdmin($certificat->evenement);

        if (!$certificat->fichierExiste()) {
            return back()->withErrors(['fichier' => 'Le fichier PDF est introuvable.']);
        }

        $cheminAbsolu = storage_path('app/public/' . $certificat->fichier_path);
        $nomTelecharge = "Certificat_{$certificat->numero}.pdf";

        return response()->download($cheminAbsolu, $nomTelecharge);
    }

    
    public function envoyerEmail(Certificat $certificat): RedirectResponse
    {
        $certificat->load('evenement', 'user');
        $this->autoriserOrganisateurOuAdmin($certificat->evenement);

        if (!$certificat->fichierExiste()) {
            return back()->withErrors(['fichier' => 'Le fichier PDF est introuvable.']);
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Bonjour {$certificat->user->prenom},\n\n" .
                "Vous trouverez en pièce jointe votre certificat de participation à l'événement \"{$certificat->evenement->titre}\".\n\n" .
                "Référence : {$certificat->numero}\n\n" .
                "Merci pour votre participation !\n\n" .
                "L'équipe " . setting('app_name', 'MoovFlow'),
                function ($message) use ($certificat) {
                    $message->to($certificat->user->email)
                            ->subject("Votre certificat - {$certificat->evenement->titre}")
                            ->attach(storage_path('app/public/' . $certificat->fichier_path), [
                                'as' => "Certificat_{$certificat->numero}.pdf",
                                'mime' => 'application/pdf',
                            ]);
                }
            );

            $certificat->update([
                'envoye_email' => true,
                'envoye_at'    => now(),
            ]);

            return back()->with('success', "Certificat envoyé à {$certificat->user->email}.");
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Erreur d\'envoi : ' . $e->getMessage()]);
        }
    }

    public function mesCertificats(): Response
    {
        $user = Auth::user();

        $certificats = Certificat::where('user_id', $user->id)
            ->with('evenement:id,titre,date_debut,date_fin')
            ->orderByDesc('genere_at')
            ->get()
            ->map(fn (Certificat $c) => [
                'id'           => $c->id,
                'numero'       => $c->numero,
                'genere_at'    => $c->genere_at->toIso8601String(),
                'evenement'    => [
                    'id'         => $c->evenement->id,
                    'titre'      => $c->evenement->titre,
                    'date_debut' => $c->evenement->date_debut?->toIso8601String(),
                ],
            ]);

        return Inertia::render('Certificats/MesCertificats', [
            'certificats' => $certificats,
        ]);
    }

    
    public function telechargerMien(Certificat $certificat): BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();

        // Vérifier que c'est bien LE SIEN
        abort_unless(
            $certificat->user_id === $user->id,
            403,
            'Ce certificat ne vous appartient pas.'
        );

        if (!$certificat->fichierExiste()) {
            return back()->withErrors(['fichier' => 'Le fichier PDF est introuvable.']);
        }

        $cheminAbsolu = storage_path('app/public/' . $certificat->fichier_path);
        $nomTelecharge = "Certificat_{$certificat->numero}.pdf";

        return response()->download($cheminAbsolu, $nomTelecharge);
    }


    private function autoriserOrganisateurOuAdmin(Evenement $evenement): void
    {
        $user = Auth::user();

        abort_unless(
            $user->hasRole('responsable_dcirp') ||
                ($user->hasRole('organisateur') && $evenement->created_by === $user->id),
            403,
            'Vous n\'avez pas accès à cet événement.'
        );
    }
}