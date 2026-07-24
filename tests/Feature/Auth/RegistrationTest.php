<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class RegistrationTest extends TestCase
{
    use RefreshDatabase;

     protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
       
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        
        $response = $this->post('/register', [
            'nom'                   => 'Dupont',
            'prenom'                => 'Marie',
            'email'                 => 'marie.dupont@example.com',
            'telephone'             => '0612345678',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('evenements.index', absolute: false));
    }
}
