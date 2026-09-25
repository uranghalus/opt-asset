<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_log_out_and_the_session_is_destroyed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_guests_cannot_log_out(): void
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('saml.redirect'));
        $this->assertGuest();
    }
}
