<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_can_open_main_admin_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $managedUser = User::factory()->create();
        $managedUser->assignRole('participant');

        Activity::query()->create([
            'log_name' => 'user',
            'description' => 'Utilisateur cree',
            'subject_type' => User::class,
            'subject_id' => $managedUser->id,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'properties' => ['attributes' => ['email' => $managedUser->email]],
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $managedUser))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $managedUser))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.audit.show', Activity::query()->firstOrFail()))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_admin_can_create_and_toggle_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'nom' => 'Doe',
                'prenom' => 'Jane',
                'email' => 'jane.doe@example.test',
                'telephone' => '0601020304',
                'password' => 'password123',
                'role' => 'participant',
                'permissions' => ['inscriptions.create'],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'jane.doe@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('participant'));

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle', $user))
            ->assertSessionHas('success');

        $this->assertFalse($user->fresh()->is_active);
    }
}