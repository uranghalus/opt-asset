<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_single_sign_on_flow()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('saml.redirect'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->forTenant()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
