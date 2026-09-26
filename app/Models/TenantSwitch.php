<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit row for one tenant switch (T01c: switches are audited from day one).
 *
 * Central table — switch events concern users and tenants, not tenant-owned
 * domain data, so it carries no tenant scope. Superseded by the full
 * audit_logs surface landing with T05.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $from_tenant_id
 * @property string $to_tenant_id
 * @property Carbon $created_at
 */
class TenantSwitch extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The user who performed the switch.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tenant acted in before the switch.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function fromTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'from_tenant_id');
    }

    /**
     * The tenant acted in after the switch.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function toTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'to_tenant_id');
    }
}
