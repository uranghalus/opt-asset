<?php

namespace App\Tenancy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void initialize(\App\Models\Tenant $tenant)
 * @method static bool initializeFromUser(\App\Models\User $user)
 * @method static bool switch(\App\Models\User $user, string $tenantId)
 * @method static \App\Models\Tenant|null resolveFor(\App\Models\User $user)
 * @method static bool check()
 * @method static string|null id()
 * @method static void end()
 *
 * @see \App\Tenancy\TenantContext
 */
class TenantContext extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \App\Tenancy\TenantContext::class;
    }
}
