<?php

namespace App\Concerns;

use App\Models\Tenant;
use App\Tenancy\FailClosedTenantScope;
use App\Tenancy\TenantContextRequiredException;
use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant scoping for every domain model.
 *
 * Wraps Stancl\Tenancy\Database\Concerns\BelongsToTenant to close its
 * documented fail-open holes (rules.md §1.1):
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
            if (! $model->getAttribute('tenant_id')) {
                if (! tenancy()->initialized || tenancy()->tenant === null) {
                    throw new TenantContextRequiredException(sprintf(
                        'Cannot create %s without an acting tenant context.',
                        $model::class,
                    ));
                }

                $model->setAttribute('tenant_id', tenancy()->tenant->getTenantKey());
            }
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
