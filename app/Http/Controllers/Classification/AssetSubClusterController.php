<?php

namespace App\Http\Controllers\Classification;

use App\Classification\CodeLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classification\SubClusterStoreRequest;
use App\Http\Requests\Classification\SubClusterUpdateRequest;
use App\Models\AssetCluster;
use App\Models\AssetSubCluster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for the fourth level of the classification chain (sub kelompok).
 *
 * Every method runs behind the tenant context and the
 * permission:classifications.manage middleware (routes/classification.php),
 * so all queries here only ever see the acting tenant's rows.
 */
class AssetSubClusterController extends Controller
{
    protected int $perPage = 10;

    /**
     * List the acting tenant's asset sub-clusters with their parent cluster
     * eager-loaded (rules §3.3), paginated at the database level.
     */
    public function index(Request $request): Response
    {
        $subClusters = AssetSubCluster::query()
            ->with('cluster')
            ->orderBy('code')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('classification/sub-clusters/index', [
            'subClusters' => $subClusters,
        ]);
    }

    /**
     * Show the create form with the acting tenant's clusters as parent
     * options.
     */
    public function create(): Response
    {
        return Inertia::render('classification/sub-clusters/create', [
            'clusters' => AssetCluster::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a new asset sub-cluster under a cluster of the acting tenant.
     *
     * Side effects: writes an `asset_sub_clusters` row and flashes a toast.
     */
    public function store(SubClusterStoreRequest $request): RedirectResponse
    {
        AssetSubCluster::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sub kelompok dibuat.'),
        ]);

        return to_route('classifications.sub-clusters.index');
    }

    /**
     * Show the edit form for an asset sub-cluster. The parent cluster is
     * never editable; the code stays editable until an item references it
     * (ADR-0001); foreign ids 404 through the tenant-scoped route model
     * binding.
     */
    public function edit(AssetSubCluster $subCluster): Response
    {
        return Inertia::render('classification/sub-clusters/edit', [
            'subCluster' => $subCluster->loadMissing('cluster'),
        ]);
    }

    /**
     * Update an asset sub-cluster. A referenced code edit surfaces as a
     * validation error naming the referencing child — never a 500
     * (ADR-0001).
     *
     * Side effects: updates the `asset_sub_clusters` row and flashes a
     * toast.
     */
    public function update(SubClusterUpdateRequest $request, AssetSubCluster $subCluster): RedirectResponse
    {
        try {
            $subCluster->fill($request->validated())->save();
        } catch (CodeLockedException) {
            throw ValidationException::withMessages([
                'code' => $subCluster->codeLockedMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sub kelompok diperbarui.'),
        ]);

        return to_route('classifications.sub-clusters.index');
    }

    /**
     * Delete an asset sub-cluster. Deleting a referenced level is blocked
     * with a validation error — the DB FK would reject it as a 500
     * otherwise (ADR-0001).
     *
     * Side effects: deletes the `asset_sub_clusters` row and flashes a
     * toast.
     */
    public function destroy(AssetSubCluster $subCluster): RedirectResponse
    {
        if ($subCluster->hasReferencingRecords()) {
            throw ValidationException::withMessages([
                'delete' => $subCluster->deleteBlockedMessage(),
            ]);
        }

        $subCluster->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sub kelompok dihapus.'),
        ]);

        return to_route('classifications.sub-clusters.index');
    }
}
