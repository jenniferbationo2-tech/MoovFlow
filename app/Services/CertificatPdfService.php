<?php

namespace App\Services;

use App\Models\Certificat;
use App\Models\Evenement;
use App\Models\Inscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class CertificatPdfService
{
    /**
     * Génère un certificat PDF pour une inscription (un participant + un événement).
     */
    public function genererPourInscription(Inscription $inscription): Certificat
    {
        // Vérifier que le participant est PRÉSENT
        if ($inscription->statut !== 'present') {
            throw new \Exception("Le participant doit être présent pour recevoir un certificat.");
        }

        // Vérifier qu'un certificat n'existe pas déjà
        $existant = Certificat::where('evenement_id', $inscription->evenement_id)
            ->where('user_id', $inscription->user_id)
            ->first();

        if ($existant && $existant->fichierExiste()) {
            return $existant; // Déjà généré, on retourne tel quel
        }

        // Charger les données nécessaires
        $inscription->loadMissing('user', 'evenement.lieu');

        // Générer un numéro unique
        $numero = Certificat::genererNumero();

        // Données pour le template
        $data = [
            'numero'       => $numero,
            'participant'  => [
                'prenom' => $inscription->user->prenom,
                'nom'    => $inscription->user->nom,
                'email'  => $inscription->user->email,
            ],
            'evenement' => [
                'titre'      => $inscription->evenement->titre,
                'date_debut' => $inscription->evenement->date_debut,
                'date_fin'   => $inscription->evenement->date_fin,
                'lieu'       => $inscription->evenement->lieu?->nom ?? 'Burkina Faso',
            ],
            'logo_base64' => $this->getLogoBase64(),
            'date_generation' => now(),
            'app_name'   => setting('app_name', 'MoovFlow'),
            'pays'       => setting('pays', 'Burkina Faso'),
        ];

        // Générer le PDF
        $pdf = Pdf::loadView('certificats.template', $data)
                  ->setPaper('a4', 'landscape'); // Format paysage

        // Chemin de stockage
        $cheminRelatif = "certificats/{$numero}.pdf";
        $cheminAbsolu = storage_path("app/public/{$cheminRelatif}");

        // S'assurer que le dossier existe
        if (!file_exists(dirname($cheminAbsolu))) {
            mkdir(dirname($cheminAbsolu), 0755, true);
        }

        // Sauvegarder le PDF
        $pdf->save($cheminAbsolu);

        // Créer ou mettre à jour l'enregistrement
        if ($existant) {
            $existant->update([
                'numero'       => $numero,
                'fichier_path' => $cheminRelatif,
                'genere_at'    => now(),
            ]);
            return $existant;
        }

        return Certificat::create([
            'numero'       => $numero,
            'evenement_id' => $inscription->evenement_id,
            'user_id'      => $inscription->user_id,
            'fichier_path' => $cheminRelatif,
            'genere_at'    => now(),
            'envoye_email' => false,
        ]);
    }

    /**
     * Génère TOUS les certificats pour un événement.
     * Retourne un résumé : ['generes' => X, 'existants' => Y, 'erreurs' => Z]
     */
    public function genererPourEvenement(Evenement $evenement): array
    {
        $resume = [
            'generes'   => 0,
            'existants' => 0,
            'erreurs'   => 0,
            'details'   => [],
        ];

        $inscriptionsPresentes = $evenement->inscriptions()
            ->where('statut', 'present')
            ->with('user')
            ->get();

        foreach ($inscriptionsPresentes as $inscription) {
            try {
                $certExistant = Certificat::where('evenement_id', $evenement->id)
                    ->where('user_id', $inscription->user_id)
                    ->first();

                if ($certExistant && $certExistant->fichierExiste()) {
                    $resume['existants']++;
                    continue;
                }

                $this->genererPourInscription($inscription);
                $resume['generes']++;
            } catch (\Exception $e) {
                $resume['erreurs']++;
                $resume['details'][] = [
                    'user' => $inscription->user?->email,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resume;
    }

    private function getLogoBase64(): ?string
    {
        // Logo depuis les settings (uploadé via Paramètres)
        $logoPath = setting('app_logo');

        if ($logoPath) {
            $fullPath = storage_path("app/public/{$logoPath}");
            if (file_exists($fullPath)) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                $data = file_get_contents($fullPath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        return null;
    }
}