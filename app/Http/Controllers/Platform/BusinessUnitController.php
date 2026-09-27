<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\BusinessUnitStoreRequest;
use App\Http\Requests\Platform\BusinessUnitTransitionRequest;
use App\Http\Requests\Platform\BusinessUnitUpdateRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Central platform admin surface for business unit management.
 *
 * The domain records are stancl `tenants` rows (the package's vocabulary);
 * the user-facing term everywhere is "unit usaha" (grill decision
 * 2026-09-27: UI language only).
 *
 * Runs in central context by design: tenancy is never initialized here, so
 * the fail-closed domain scope never applies and central `tenants` rows are
 * administrable. Guarded upstream by EnsurePlatformAdmin.
 */
class BusinessUnitController extends Controller
{
    /**
     * Number of business units per page on the index.
     */
    protected int $perPage = 10;

    /**
     * Display the business unit list: server-side pagination, search
     * (code/name), and status filter.
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

        return Inertia::render('platform/business-units/index', [
            'businessUnits' => $tenants,
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
        return Inertia::render('platform/business-units/create');
    }

    /**
     * Store a new business unit (status defaults to active).
     */
    public function store(BusinessUnitStoreRequest $request): RedirectResponse
    {
        Tenant::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Unit usaha dibuat.'),
        ]);

        return to_route('platform.business-units.index');
    }

    /**
     * Show the edit form for a business unit.
     */
    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('platform/business-units/edit', [
            'businessUnit' => $tenant,
        ]);
    }

    /**
     * Update a business unit's code and name.
     */
    public function update(BusinessUnitUpdateRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->fill($request->validated());
        $tenant->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Unit usaha diperbarui.'),
        ]);

        return to_route('platform.business-units.index');
    }

    /**
     * Transition a business unit's status (no hard delete — status changes
     * only, per the settled decision). Suspending fail-closes the unit's
     * users on their next request through the tenant middleware.
     */
    public function transition(BusinessUnitTransitionRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->status = $request->validated('status');
        $tenant->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Status unit usaha diperbarui.'),
        ]);

        return back();
    }
}
