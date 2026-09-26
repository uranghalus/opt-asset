<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $saml_name_id
 * @property string $password
 * @property string $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantMembership> $memberships
 * @property-read Collection<int, Tenant> $tenants
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The user's tenant memberships (multi-membership, T01c).
     *
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * All tenants the user is a member of, through the pivot.
     *
     * @return BelongsToMany<Tenant, $this, TenantMembership, 'pivot'>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_memberships')
            ->using(TenantMembership::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * The user's default membership, if any.
     */
    public function defaultMembership(): ?TenantMembership
    {
        return $this->memberships->firstWhere('is_default', true);
    }

    /**
     * Whether this account may administer the central platform area:
     * zero tenant memberships AND an allowlisted email (T01c — the
     * fail-closed replacement for "any JIT user without a tenant").
     */
    public function isPlatformAdmin(): bool
    {
        return $this->memberships()->doesntExist()
            && in_array(strtolower($this->email), config('platform.admin_emails', []), true);
    }
}
