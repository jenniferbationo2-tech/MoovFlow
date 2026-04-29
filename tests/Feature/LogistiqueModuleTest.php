<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Lieu;
use App\Models\TypeEvenement;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogistiqueModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    /**
     * Vérifie l'affichage du module logistique pour un événement.
     */
    public function test_authenticated_user_can_open_logistique_pages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('organisateur');

        $type = TypeEvenement::query()->create([
            'nom' => 'Conférence',
            'code' => 'conference',
        ]);

        $lieu = Lieu::query()->create([
            'nom' => 'Palais des congrès',
            'adresse' => 'Libreville',
        ]);

        $evenement = Evenement::query()->create([
            'titre' => 'Sommet logistique',
            'description' => 'Test',
            'type_evenement_id' => $type->id,
            'date_debut' => now(),
            'date_fin' => now()->addDay(),
            'lieu_id' => $lieu->id,
            'statut' => 'brouillon',
            'budget_prev' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('logistique.ressources.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('logistique.intervenants.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('logistique.benevoles.index', $evenement))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('logistique.dotations.index', $evenement))
            ->assertOk();
    }
}