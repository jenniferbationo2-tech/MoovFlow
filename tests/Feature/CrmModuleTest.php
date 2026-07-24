<?php

namespace Tests\Feature;

use App\Models\B2BMeeting;
use App\Models\Evenement;
use App\Models\Followup;
use App\Models\Lieu;
use App\Models\MootNotification;
use App\Models\TypeEvenement;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionSeeder::class);
    }

    /**
     * Vérifie l'ouverture des principales pages CRM.
     */
    public function test_authenticated_user_can_open_crm_pages(): void
    {
        [$admin, $participant, $evenement] = $this->createContext();

        Followup::query()->create([
            'user_id' => $participant->id,
            'evenement_id' => $evenement->id,
            'type' => 'relance',
            'statut' => 'a_faire',
            'notes' => 'Relancer le contact',
            'date_prevue' => now()->toDateString(),
        ]);

        B2BMeeting::query()->create([
            'evenement_id' => $evenement->id,
            'organisateur_id' => $admin->id,
            'prospect_id' => $participant->id,
            'prospect_nom' => $participant->name,
            'prospect_email' => $participant->email,
            'prospect_societe' => 'ACME',
            'objet' => 'Discussion partenariat',
            'date_rdv' => now()->addDay(),
            'statut' => 'planifie',
            'score_lead' => 75,
        ]);

        $this->actingAs($admin)->get(route('crm.contacts.index'))->assertOk();
        $this->actingAs($admin)->get(route('crm.contacts.show', $participant))->assertOk();
        $this->actingAs($admin)->get(route('crm.followups.index'))->assertOk();
        $this->actingAs($admin)->get(route('crm.b2b.index', $evenement))->assertOk();
        $this->actingAs($admin)->get(route('crm.loyalty.index'))->assertOk();
    }

    /**
     * Vérifie les actions principales du module CRM.
     */
    public function test_authenticated_user_can_execute_crm_actions(): void
    {
        [$admin, $participant, $evenement] = $this->createContext();

        $this->actingAs($admin)
            ->post(route('crm.followups.store'), [
                'user_id' => $participant->id,
                'evenement_id' => $evenement->id,
                'type' => 'remerciement',
                'statut' => 'a_faire',
                'notes' => 'Message de remerciement',
                'date_prevue' => now()->toDateString(),
            ])
            ->assertSessionHas('success');

        $followup = Followup::query()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('crm.followups.update', $followup), [
                'type' => 'remerciement',
                'statut' => 'fait',
                'notes' => 'Message envoyé',
                'date_prevue' => now()->toDateString(),
                'date_realise' => now()->toDateString(),
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('crm.b2b.store', $evenement), [
                'prospect_id' => $participant->id,
                'objet' => 'Rencontre sponsor',
                'date_rdv' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'lieu' => 'Stand B2B',
                'statut' => 'confirme',
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('crm.sync'))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('crm.thank-you', $evenement))
            ->assertSessionHas('success');

        $futureEvenement = Evenement::query()->create([
            'titre' => 'Forum innovation',
            'description' => 'Édition à venir',
            'type_evenement_id' => $evenement->type_evenement_id,
            'date_debut' => now()->addMonth(),
            'date_fin' => now()->addMonth()->addDay(),
            'lieu_id' => $evenement->lieu_id,
            'statut' => 'publie',
            'budget_prev' => 250000,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('crm.loyalty.invite'), [
                'user_id' => $participant->id,
                'evenement_id' => $futureEvenement->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('followups', [
            'user_id' => $participant->id,
            'statut' => 'fait',
        ]);

        $this->assertDatabaseHas('b2b_meetings', [
            'evenement_id' => $evenement->id,
            'prospect_id' => $participant->id,
            'statut' => 'confirme',
        ]);

        $this->assertDatabaseHas('moot_notifications', [
            'user_id' => $participant->id,
            'type' => 'invitation_prioritaire',
        ]);
    }

    /**
     * Prépare un contexte CRM minimal.
     *
     * @return array{0: User, 1: User, 2: Evenement}
     */
    private function createContext(): array
    {
        $admin = User::factory()->create([
            'telephone' => '0700000000',
        ]);
        $admin->assignRole('admin');

        $participant = User::factory()->create([
            'telephone' => '0600000000',
        ]);
        $participant->assignRole('participant');

        $type = TypeEvenement::firstOrCreate([
            'nom' => 'Salon',
            'code' => 'salon',
        ]);

        $lieu = Lieu::query()->create([
            'nom' => 'Palais des congrès',
            'adresse' => 'Libreville',
        ]);

        $evenement = Evenement::query()->create([
            'titre' => 'Salon B2B',
            'description' => 'Événement CRM',
            'type_evenement_id' => $type->id,
            'date_debut' => now()->subDays(2),
            'date_fin' => now()->subDay(),
            'lieu_id' => $lieu->id,
            'statut' => 'termine',
            'budget_prev' => 100000,
            'created_by' => $admin->id,
        ]);

        $evenement->inscriptions()->create([
            'user_id' => $participant->id,
            'tarif_id' => null,
            'statut' => 'confirmee',
            'qr_code' => 'crm-qr-code',
        ]);

        MootNotification::query()->create([
            'user_id' => $participant->id,
            'type' => 'manuel',
            'canal' => 'email',
            'titre' => 'Bienvenue',
            'message' => 'Historique CRM initial',
            'statut' => 'envoye',
            'date_envoi' => now(),
        ]);

        return [$admin, $participant, $evenement];
    }
}