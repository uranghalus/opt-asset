<?php

namespace App\Http\Controllers\Classification;

use App\Classification\CodeLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classification\ClusterStoreRequest;
use App\Http\Requests\Classification\ClusterUpdateRequest;
use App\Models\AssetCategory;
use App\Models\AssetCluster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for the third level of the classification chain (kelompok).
 *
 * Every method runs behind the tenant context and the
 * permission:classifications.manage middleware (routes/classification.php),
 * so all queries here only ever see the acting tenant's rows.
 */
class AssetClusterController extends Controller
{
    protected int $perPage = 10;

    /**
     * List the acting tenant's asset clusters with their parent category
     * eager-loaded (rules §3.3), paginated at the database level.
     */
    public function index(Request $request): Response
    {
        $clusters = AssetCluster::query()
            ->with('category')
            ->orderBy('code')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('classification/clusters/index', [
            'clusters' => $clusters,
        ]);
    }

    /**
     * Show the create form with the acting tenant's categories as parent
     * options.
     */
    public function create(): Response
    {
        return Inertia::render('classification/clusters/create', [
            'categories' => AssetCategory::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a new asset cluster under a category of the acting tenant.
     *
     * Side effects: writes an `asset_clusters` row and flashes a toast.
     */
    public function store(ClusterStoreRequest $request): RedirectResponse
    {
        AssetCluster::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kelompok dibuat.'),
        ]);

        return to_route('classifications.clusters.index');
    }

    /**
     * Show the edit form for an asset cluster. The parent category is never
     * editable; the code stays editable until a child level references it
     * (ADR-0001); foreign ids 404 through the tenant-scoped route model
     * binding.
     */
    public function edit(AssetCluster $cluster): Response
    {
        return Inertia::render('classification/clusters/edit', [
            'cluster' => $cluster->loadMissing('category'),
        ]);
    }

    /**
     * Update an asset cluster. A referenced code edit surfaces as a
     * validation error naming the referencing child — never a 500
     * (ADR-0001).
     *
     * Side effects: updates the `asset_clusters` row and flashes a toast.
     */
    public function update(ClusterUpdateRequest $request, AssetCluster $cluster): RedirectResponse
    {
        try {
            $cluster->fill($request->validated())->save();
        } catch (CodeLockedException) {
            throw ValidationException::withMessages([
                'code' => $cluster->codeLockedMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kelompok diperbarui.'),
        ]);

        return to_route('classifications.clusters.index');
    }

    /**
     * Delete an asset cluster. Deleting a referenced level is blocked with
     * a validation error — the DB FK would reject it as a 500 otherwise
     * (ADR-0001).
     *
     * Side effects: deletes the `asset_clusters` row and flashes a toast.
     */
    public function destroy(AssetCluster $cluster): RedirectResponse
    {
        if ($cluster->hasReferencingRecords()) {
            throw ValidationException::withMessages([
                'delete' => $cluster->deleteBlockedMessage(),
            ]);
        }

        $cluster->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kelompok dihapus.'),
        ]);

        return to_route('classifications.clusters.index');
    }
}
