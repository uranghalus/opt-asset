<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Fail-closed tenant scope: the hole-closing replacement for
 * Stancl\Tenancy\Database\TenantScope, which returns unscoped results when
 * tenancy is not initialized (documented fail-open behavior — see
 * docs/research/2026-09-27-tenancy-reconfiguration.md).
 *
 * Semantics:
 *  - tenant initialized  → WHERE tenant_id = <acting tenant key>;
 *  - no tenant context   → always-false predicate, zero rows, never unscoped;
 *  - `withoutTenancy` macro preserved for explicit central maintenance paths.
 *
 * Reads stay implicit so every list/find across the app is protected by
 * default; writes refuse to run without a context (stamping happens in the
 * BelongsToTenant creating hook), which surfaces the misconfiguration at the
 * call site instead of silently persisting orphaned rows.
 *
 * @implements Scope<Model>
 */
class FailClosedTenantScope implements Scope
{
    /**
     * Apply the tenant filter to the query.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = tenancy()->initialized ? tenancy()->tenant : null;

        if ($tenant instanceof TenantContract) {
            $builder->where(
                $model->qualifyColumn('tenant_id'),
                $tenant->getTenantKey(),
            );

            return;
        }

        $builder->whereRaw('1 = 0');
    }

    /**
     * Keep the escape hatch macro the package provides, for explicit
     * central-context maintenance code paths only.
     *
     * @param  Builder<Model>  $builder
     */
    public function extend(Builder $builder): void
    {
        $scope = $this;

        $builder->macro('withoutTenancy', function (Builder $builder) use ($scope) {
            return $builder->withoutGlobalScope($scope);
        });
    }
}
