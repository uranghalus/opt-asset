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
 * @property bool $is_superadmin
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
            'is_superadmin' => 'boolean',
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
     * Promote this account to superadmin when its email is on the bootstrap
     * allowlist (config platform.admin_emails). Called from the SAML JIT
     * path; safe to call repeatedly — promotion is one-way from this entry
     * point, and revocation happens in data (T11 admin UI).
     */
    public function promoteIfAllowlisted(): void
    {
        $allowlist = array_map('strtolower', (array) config('platform.admin_emails', []));

        if (in_array(strtolower($this->email), $allowlist, true) && ! $this->is_superadmin) {
            $this->forceFill(['is_superadmin' => true])->save();
        }
    }

    /**
     * Whether this account may administer the central platform area
     * (T01d): the data-driven superadmin flag — with or without tenant
     * memberships. The zero-membership rule from T01c is superseded.
     */
    public function isPlatformAdmin(): bool
    {
        // Defensive null-coalesce: in-memory instances created before the
        // attribute was set (e.g. pre-insert forceFill paths) would return
        // null despite the column's false default.
        return (bool) ($this->is_superadmin ?? false);
    }
}
