<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_single_sign_on_flow()
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('saml.redirect'));
    }

    public function test_authenticated_users_are_redirected_to_the_dashboard()
    {
        $this->actingAs(User::factory()->forTenant()->create());

        $response = $this->get(route('home'));

        $response->assertRedirect(route('dashboard'));
    }
}
