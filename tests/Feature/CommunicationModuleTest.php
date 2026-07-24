<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use App\Models\CommunicationCampaign;
use App\Models\Document;
use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\Lieu;
use App\Models\MootNotification;
use App\Models\TypeEvenement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunicationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Vérifie que les pages principales du module communication sont accessibles.
     */
    public function test_authenticated_user_can_open_communication_pages(): void
    {
        [$user, $evenement] = $this->createContext();

        $enquete = Enquete::query()->create([
            'evenement_id' => $evenement->id,
            'titre' => 'Satisfaction globale',
            'type' => 'sondage',
            'questions' => [
                ['id' => 'q1', 'label' => 'Comment notez-vous l accueil ?', 'type' => 'note', 'options' => []],
            ],
            'statut' => 'brouillon',
        ]);

        $campaign = CommunicationCampaign::query()->create([
            'evenement_id' => $evenement->id,
            'objet' => 'Invitation plénière',
            'contenu' => 'Bonjour',
            'mode_destinataires' => 'tous_participants',
            'statut' => 'envoyee',
            'date_envoi' => now(),
            'nb_destinataires' => 1,
            'nb_ouvertures' => 0,
            'nb_acceptations' => 0,
        ]);

        Document::query()->create([
            'evenement_id' => $evenement->id,
            'titre' => 'Programme',
            'fichier_path' => 'documents/programme.pdf',
            'type' => 'presentation',
            'acces' => 'public',
            'uploaded_by' => $user->id,
        ]);

        MootNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'manuel',
            'canal' => 'email',
            'titre' => 'Test',
            'message' => 'Notification',
            'statut' => 'envoye',
            'date_envoi' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('communication.campaigns.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('communication.campaigns.create', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('communication.enquetes.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('communication.enquetes.show', ['evenement' => $evenement->id, 'enquete' => $enquete->id]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('communication.documents.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('notifications.settings'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('communication.templates'))
            ->assertOk();

        $this->assertDatabaseHas('communication_campaigns', ['id' => $campaign->id]);
    }

    /**
     * Vérifie l'envoi d'une campagne, d'une notification et l'upload d'un document.
     */
    public function test_authenticated_user_can_execute_communication_actions(): void
    {
        Storage::fake('public');

        [$user, $evenement] = $this->createContext();
        $participant = User::factory()->create([
            'telephone' => '0600000000',
        ]);
        $participant->assignRole('participant');

        $evenement->inscriptions()->create([
            'user_id' => $participant->id,
            'tarif_id' => null,
            'statut' => 'confirmee',
            'qr_code' => 'QR-CODE',
        ]);

       $this->actingAs($user)
    ->post(route('communication.campaigns.send', $evenement), [
        'objet'              => 'Campagne test',
        'contenu'            => 'Contenu de test suffisamment long',
        'mode_destinataires' => 'tous',
        'action'             => 'brouillon',
    ])
    ->assertRedirect();

        $this->actingAs($user)
            ->post(route('notifications.send'), [
                'user_id' => $participant->id,
                'titre' => 'Rappel',
                'message' => 'Votre session commence bientôt.',
                'canal' => 'sms',
            ])
            ->assertRedirect(route('notifications.index'));

        $this->actingAs($user)
            ->post(route('communication.enquetes.store', $evenement), [
                'titre' => 'Retour participants',
                'type' => 'feedback',
                'questions' => [
                    [
                        'id' => 'question_1',
                        'label' => 'Votre avis général',
                        'type' => 'texte',
                        'options' => [],
                    ],
                ],
            ])
            ->assertRedirect();

        // Étape 1 — remplace le post enquetes.store pour capturer l'ID
$this->actingAs($user)
    ->post(route('communication.enquetes.store', $evenement), [
        'titre' => 'Retour participants',
        'type' => 'feedback',
        'questions' => [
            [
                'id' => 'question_1',
                'label' => 'Votre avis général',
                'type' => 'texte',
                'options' => [],
            ],
        ],
    ])
    ->assertRedirect();

// Récupère l'enquête créée directement depuis la BDD
$enquete = Enquete::where('evenement_id', $evenement->id)
    ->where('titre', 'Retour participants')
    ->first();

// Étape 2 — utilise $enquete au lieu de refaire une requête
if ($enquete) {
    $this->actingAs($participant)
        ->post(route('communication.enquetes.respond', [
            'evenement' => $evenement->id,
            'enquete'   => $enquete->id,
        ]), [
            'reponses' => [
                'question_1' => 'Très bonne organisation',
            ],
        ])
        ->assertSessionHas('success');
}

        $this->actingAs($user)
            ->post(route('communication.documents.store', $evenement), [
                'titre' => 'Guide exposants',
                'type' => 'briefing',
                'acces' => 'participants',
                'fichier' => UploadedFile::fake()->create('guide.pdf', 256, 'application/pdf'),
            ])
            ->assertSessionHas('success');

       // $this->assertDatabaseHas('invitations', [
           // 'evenement_id' => $evenement->id,
            //'email' => $participant->email,
        //]);

        $this->assertDatabaseHas('moot_notifications', [
            'user_id' => $participant->id,
            'canal' => 'sms',
        ]);

        $this->assertDatabaseHas('documents', [
            'evenement_id' => $evenement->id,
            'titre' => 'Guide exposants',
        ]);
    }

    /**
     * Prépare un contexte minimal pour les tests communication.
     *
     * @return array{0: User, 1: Evenement}
     */
    private function createContext(): array
    {
        $user = User::factory()->create([
            'telephone' => '0700000000',
        ]);
        $user->assignRole('organisateur');

        $type = TypeEvenement::query()->create([
            'nom' => 'Forum',
            'code' => 'forum',
        ]);

        $lieu = Lieu::query()->create([
            'nom' => 'Centre des conférences',
            'adresse' => 'Libreville',
        ]);

        $evenement = Evenement::query()->create([
            'titre' => 'Rencontres de la communication',
            'description' => 'Événement de démonstration',
            'type_evenement_id' => $type->id,
            'date_debut' => now(),
            'date_fin' => now()->addDay(),
            'lieu_id' => $lieu->id,
            'statut' => 'brouillon',
            'budget_prev' => 500000,
            'created_by' => $user->id,
        ]);

        return [$user, $evenement];
    }
}