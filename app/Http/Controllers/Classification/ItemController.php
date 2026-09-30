<?php

namespace App\Http\Controllers\Classification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classification\ItemStoreRequest;
use App\Http\Requests\Classification\ItemUpdateRequest;
use App\Models\AssetSubCluster;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for items — the leaf level of the classification chain.
 *
 * Items carry no code column (their identity is the per-tenant unique
 * name); every method runs behind the tenant context and the
 * permission:classifications.manage middleware (routes/classification.php),
 * so all queries here only ever see the acting tenant's rows.
 */
class ItemController extends Controller
{
    protected int $perPage = 10;

    /**
     * List the acting tenant's items with their sub-cluster eager-loaded
     * (rules §3.3), paginated at the database level.
     */
    public function index(Request $request): Response
    {
        $items = Item::query()
            ->with('subCluster')
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('classification/items/index', [
            'items' => $items,
        ]);
    }

    /**
     * Show the create form with the acting tenant's sub-clusters as parent
     * options (an item may also stay unclassified).
     */
    public function create(): Response
    {
        return Inertia::render('classification/items/create', [
            'subClusters' => AssetSubCluster::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a new item for the acting tenant.
     *
     * Side effects: writes an `items` row and flashes a toast.
     */
    public function store(ItemStoreRequest $request): RedirectResponse
    {
        Item::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Item dibuat.'),
        ]);

        return to_route('classifications.items.index');
    }

    /**
     * Show the edit form for an item. Foreign ids 404 through the
     * tenant-scoped route model binding.
     */
    public function edit(Item $item): Response
    {
        return Inertia::render('classification/items/edit', [
            'item' => $item->loadMissing('subCluster'),
            'subClusters' => AssetSubCluster::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Update an item.
     *
     * Side effects: updates the `items` row and flashes a toast.
     */
    public function update(ItemUpdateRequest $request, Item $item): RedirectResponse
    {
        $item->fill($request->validated())->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Item diperbarui.'),
        ]);

        return to_route('classifications.items.index');
    }

    /**
     * Delete an item. Items are the leaf level of the chain, so nothing
     * references them and deletion always succeeds.
     *
     * Side effects: deletes the `items` row and flashes a toast.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Item dihapus.'),
        ]);

        return to_route('classifications.items.index');
    }
}
