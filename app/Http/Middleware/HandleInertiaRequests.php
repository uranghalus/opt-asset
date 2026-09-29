<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Resolved once per request: the acting tenant backs both the
        // auth.tenant_id payload and the switcher's active state.
        $activeTenant = $user === null
            ? null
            : app(TenantContext::class)->resolveFor($user);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,

                // The tenant id resolved for this session, set into the
                // user data on login (grill decision 2026-09-27): the
                // membership pivot + session pointer stay the source of
                // truth; this exposes the resolved value to the frontend.
                'tenant_id' => $activeTenant?->getKey(),
            ],

            // Tenant switcher data: platform admins may switch into ANY
            // active tenant; regular users only into their memberships.
            // Suspended tenants are never switchable, fail-closed.
            'tenancy' => $user === null
                ? ['switchable' => collect(), 'active' => null]
                : [
                    'switchable' => $user->isPlatformAdmin()
                        ? Tenant::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code'])
                        : $user->tenants()->where('tenants.status', 'active')->orderBy('name')->get(['tenants.id', 'tenants.name', 'tenants.code']),
                    'active' => $activeTenant !== null
                        ? ['id' => $activeTenant->id, 'name' => $activeTenant->name, 'code' => $activeTenant->code]
                        : null,
                ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
