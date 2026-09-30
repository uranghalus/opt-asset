<?php

namespace App\Http\Controllers\Classification;

use App\Classification\CodeLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classification\CategoryStoreRequest;
use App\Http\Requests\Classification\CategoryUpdateRequest;
use App\Models\AssetCategory;
use App\Models\AssetGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for the second level of the classification chain (kategori).
 *
 * Every method runs behind the tenant context and the
 * permission:classifications.manage middleware (routes/classification.php),
 * so all queries here only ever see the acting tenant's rows.
 */
class AssetCategoryController extends Controller
{
    protected int $perPage = 10;

    /**
     * List the acting tenant's asset categories with their parent group
     * eager-loaded (rules §3.3), paginated at the database level.
     */
    public function index(Request $request): Response
    {
        $categories = AssetCategory::query()
            ->with('group')
            ->orderBy('code')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('classification/categories/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the create form with the acting tenant's groups as parent
     * options.
     */
    public function create(): Response
    {
        return Inertia::render('classification/categories/create', [
            'groups' => AssetGroup::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Store a new asset category under a group of the acting tenant.
     *
     * Side effects: writes an `asset_categories` row and flashes a toast.
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        AssetCategory::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kategori dibuat.'),
        ]);

        return to_route('classifications.categories.index');
    }

    /**
     * Show the edit form for an asset category. The parent group is never
     * editable; the code stays editable until a child level references it
     * (ADR-0001); foreign ids 404 through the tenant-scoped route model
     * binding.
     */
    public function edit(AssetCategory $category): Response
    {
        return Inertia::render('classification/categories/edit', [
            'category' => $category->loadMissing('group'),
        ]);
    }

    /**
     * Update an asset category. A referenced code edit surfaces as a
     * validation error naming the referencing child — never a 500
     * (ADR-0001).
     *
     * Side effects: updates the `asset_categories` row and flashes a toast.
     */
    public function update(CategoryUpdateRequest $request, AssetCategory $category): RedirectResponse
    {
        try {
            $category->fill($request->validated())->save();
        } catch (CodeLockedException) {
            throw ValidationException::withMessages([
                'code' => $category->codeLockedMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kategori diperbarui.'),
        ]);

        return to_route('classifications.categories.index');
    }

    /**
     * Delete an asset category. Deleting a referenced level is blocked with
     * a validation error — the DB FK would reject it as a 500 otherwise
     * (ADR-0001).
     *
     * Side effects: deletes the `asset_categories` row and flashes a toast.
     */
    public function destroy(AssetCategory $category): RedirectResponse
    {
        if ($category->hasReferencingRecords()) {
            throw ValidationException::withMessages([
                'delete' => $category->deleteBlockedMessage(),
            ]);
        }

        $category->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Kategori dihapus.'),
        ]);

        return to_route('classifications.categories.index');
    }
}
