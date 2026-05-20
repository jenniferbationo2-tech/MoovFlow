<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Trouver / créer l'inscription présente
$user = \App\Models\User::where('email', 'participant@test.bf')->first();
$evenement = \App\Models\Evenement::first();

if (!$user) {
    die("❌ Participant introuvable\n");
}
if (!$evenement) {
    die("❌ Aucun événement\n");
}

echo "User : {$user->prenom} {$user->nom}\n";
echo "Événement : {$evenement->titre}\n";

$inscription = \App\Models\Inscription::firstOrCreate(
    ['user_id' => $user->id, 'evenement_id' => $evenement->id],
    ['reference' => 'INS-CERT-' . rand(100, 999), 'statut' => 'presente']
);

$inscription->update(['statut' => 'presente']);
$inscription->refresh();

echo "Inscription #{$inscription->id} statut = {$inscription->statut}\n";

// 2. Générer le certificat
try {
    $service = new \App\Services\CertificatPdfService();
    $certificat = $service->genererPourInscription($inscription);
    
    echo "\n✅ CERTIFICAT GÉNÉRÉ !\n";
    echo "Numéro : {$certificat->numero}\n";
    echo "Fichier : {$certificat->fichier_path}\n";
    echo "URL : {$certificat->url}\n";
    echo "\nOuvre dans ton navigateur :\n";
    echo "http://127.0.0.1:8000/storage/{$certificat->fichier_path}\n";
} catch (\Exception $ex) {
    echo "\n❌ ERREUR : " . $ex->getMessage() . "\n";
    echo "Trace : " . $ex->getTraceAsString() . "\n";
}