<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'is_superadmin' => false,
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Attach the user to the given tenant (creates one when omitted) via a
     * default membership (T01c multi-membership).
     */
    public function forTenant(?Tenant $tenant = null): static
    {
        return $this->afterCreating(function (User $user) use ($tenant) {
            TenantMembership::query()->create([
                'user_id' => $user->id,
                'tenant_id' => ($tenant ?? Tenant::factory()->create())->id,
                'is_default' => true,
            ]);
        });
    }

    /**
     * Give the user a SAML NameID as if provisioned through SSO.
     */
    public function saml(): static
    {
        return $this->state(fn (array $attributes) => [
            'saml_name_id' => (string) str()->ulid(),
        ]);
    }
}
