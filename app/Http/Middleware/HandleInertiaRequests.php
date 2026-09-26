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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],

            // Tenant switcher data (T01c/T01d): superadmins may switch into
            // ANY active tenant; regular users only into their memberships.
            // Suspended tenants are never switchable, fail-closed.
            'tenancy' => $user === null
                ? ['switchable' => collect(), 'active' => null]
                : [
                    'switchable' => $user->is_superadmin
                        ? Tenant::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code'])
                        : $user->tenants()->where('tenants.status', 'active')->orderBy('name')->get(['tenants.id', 'tenants.name', 'tenants.code']),
                    'active' => ($tenant = app(TenantContext::class)->resolveFor($user)) !== null
                        ? ['id' => $tenant->id, 'name' => $tenant->name, 'code' => $tenant->code]
                        : null,
                ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
