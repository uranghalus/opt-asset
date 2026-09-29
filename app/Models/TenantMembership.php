<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One user's membership in one tenant (business unit).
 *
 * Deliberately NOT tenant-scoped (no BelongsToTenant): a user's membership
 * list must stay readable from inside any tenant context, otherwise the
 * switcher could not offer other tenants. Cross-tenant readability is the
 * design (isolation test: TenantMembershipTest).
 *
 * @property int $id
 * @property int $user_id
 * @property string $tenant_id
 * @property bool $is_default
 */
class TenantMembership extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_memberships';

    /**
     * Indicates if the pivot table has timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * The tenant this membership grants access to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The member user.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Make this membership the user's only default.
     */
    public function makeDefault(): void
    {
        static::query()
            ->where('user_id', $this->user_id)
            ->whereKeyNot($this->getKey())
            ->update(['is_default' => false]);

        $this->forceFill(['is_default' => true])->save();
    }
}
