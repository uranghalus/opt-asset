<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\TenantStoreRequest;
use App\Http\Requests\Platform\TenantTransitionRequest;
use App\Http\Requests\Platform\TenantUpdateRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Central platform admin surface for tenant management (T01b).
 *
 * Runs in central context by design: tenancy is never initialized here, so
 * the fail-closed domain scope never applies and central `tenants` rows are
 * administrable. Guarded upstream by EnsurePlatformAdmin.
 */
class TenantController extends Controller
{
    /**
     * Number of tenants per page on the index.
     */
    protected int $perPage = 10;

    /**
     * Display the tenant list: server-side pagination, search (code/name),
     * and status filter.
     */
    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->when(
                $request->string('search')->trim()->toString() !== '',
                fn ($query) => $query->where(function ($query) use ($request): void {
                    $term = '%'.$request->string('search')->trim()->toString().'%';

                    $query
                        ->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                }),
            )
            ->when(
                in_array($request->string('status')->toString(), ['active', 'inactive', 'suspended'], true),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('platform/tenants/index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $request->string('search')->trim()->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): Response
    {
        return Inertia::render('platform/tenants/create');
    }

    /**
     * Store a new tenant (status defaults to active).
     */
    public function store(TenantStoreRequest $request): RedirectResponse
    {
        Tenant::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tenant created.'),
        ]);

        return to_route('platform.tenants.index');
    }

    /**
     * Show the edit form for a tenant.
     */
    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('platform/tenants/edit', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update a tenant's code and name.
     */
    public function update(TenantUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->fill($request->validated());
        $tenant->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tenant updated.'),
        ]);

        return to_route('platform.tenants.index');
    }

    /**
     * Transition a tenant's status (no hard delete — status changes only,
     * per the settled decision). Suspending fail-closes the tenant's users
     * on their next request through the T01 middleware.
     */
    public function transition(TenantTransitionRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->status = $request->validated('status');
        $tenant->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Tenant status updated.'),
        ]);

        return back();
    }
}
