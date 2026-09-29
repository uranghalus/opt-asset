<?php

namespace App\Concerns;

use App\Models\Tenant;
use App\Tenancy\FailClosedTenantScope;
use App\Tenancy\TenantContextRequiredException;
use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Tenant scoping for every domain model.
 *
 * Wraps Stancl\Tenancy\Database\Concerns\BelongsToTenant to close its
 * documented fail-open holes (docs/research/2026-09-27-tenancy-reconfiguration.md):
 *
 *  - queries use App\Tenancy\FailClosedTenantScope: initialized tenant →
 *    filtered; no context → zero rows, never an unscoped scan;
 *  - creating a record with no acting tenant throws
 *    TenantContextRequiredException instead of silently inserting a null
 *    tenant_id row;
 *  - a tenant_id arriving from request input (mass assignment) never wins
 *    over the acting tenant — the creating hook overwrites it.
 *
 * Jobs and Artisan commands share the exact same semantics by initializing
 * tenancy explicitly (tenancy()->initialize($tenant) / $tenant->run()).
 *
 * @property string $tenant_id
 */
trait BelongsToTenant
{
    /**
     * The central tenant this record belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Boot the trait: install the fail-closed scope and the stamping guard.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new FailClosedTenantScope);

        static::creating(function (self $model): void {
            // TenantContext convention: narrow stancl's `Tenant|Model|null`
            // to the contract — a bound tenant IS a TenantContract.
            $tenant = tenancy()->tenant;

            if (! $tenant instanceof TenantContract) {
                if (! $model->getAttribute('tenant_id')) {
                    throw new TenantContextRequiredException(sprintf(
                        'Cannot create %s without an acting tenant context.',
                        $model::class,
                    ));
                }

                // Fixture-style writes (migrations, historical imports) may
                // pre-stamp a tenant explicitly; without a context there is
                // nothing to protect, so the explicit value stands.
                return;
            }

            // The acting tenant always wins: a tenant_id arriving from
            // request input (mass assignment) must never cross the
            // isolation boundary, even explicitly.
            $model->setAttribute('tenant_id', $tenant->getTenantKey());
        });
    }

    /**
     * Initialize tenancy for the given tenant and run the callback inside
     * it — the standard entry point for queue jobs and Artisan commands
     * that touch tenant-owned models.
     *
     * @param  (Closure(Tenant): mixed)|null  $callback
     */
    public static function forTenant(Tenant $tenant, ?Closure $callback = null): mixed
    {
        if ($callback === null) {
            tenancy()->initialize($tenant);

            return null;
        }

        return $tenant->run($callback);
    }
}
