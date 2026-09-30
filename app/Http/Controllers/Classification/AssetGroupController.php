<?php

namespace App\Http\Controllers\Classification;

use App\Classification\CodeLockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Classification\GroupStoreRequest;
use App\Http\Requests\Classification\GroupUpdateRequest;
use App\Models\AssetGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD for the top level of the classification chain (golongan).
 *
 * Every method runs behind the tenant context and the
 * permission:classifications.manage middleware (routes/classification.php),
 * so all queries here only ever see the acting tenant's rows.
 */
class AssetGroupController extends Controller
{
    protected int $perPage = 10;

    /**
     * List the acting tenant's asset groups, paginated at the database
     * level (rules §3.3).
     */
    public function index(Request $request): Response
    {
        $groups = AssetGroup::query()
            ->orderBy('code')
            ->paginate($this->perPage)
            ->withQueryString();

        return Inertia::render('classification/groups/index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show the create form. Groups are the top level, so no parent options
     * are needed.
     */
    public function create(): Response
    {
        return Inertia::render('classification/groups/create');
    }

    /**
     * Store a new asset group for the acting tenant.
     *
     * Side effects: writes an `asset_groups` row and flashes a toast.
     */
    public function store(GroupStoreRequest $request): RedirectResponse
    {
        AssetGroup::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Golongan dibuat.'),
        ]);

        return to_route('classifications.groups.index');
    }

    /**
     * Show the edit form for an asset group. The code stays editable until
     * a child level references it (ADR-0001); foreign ids 404 through the
     * tenant-scoped route model binding.
     */
    public function edit(AssetGroup $group): Response
    {
        return Inertia::render('classification/groups/edit', [
            'group' => $group,
        ]);
    }

    /**
     * Update an asset group. A referenced code edit surfaces as a
     * validation error naming the referencing child — never a 500
     * (ADR-0001).
     *
     * Side effects: updates the `asset_groups` row and flashes a toast.
     */
    public function update(GroupUpdateRequest $request, AssetGroup $group): RedirectResponse
    {
        try {
            $group->fill($request->validated())->save();
        } catch (CodeLockedException) {
            throw ValidationException::withMessages([
                'code' => $group->codeLockedMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Golongan diperbarui.'),
        ]);

        return to_route('classifications.groups.index');
    }

    /**
     * Delete an asset group. Deleting a referenced level is blocked with a
     * validation error — the DB FK would reject it as a 500 otherwise
     * (ADR-0001).
     *
     * Side effects: deletes the `asset_groups` row and flashes a toast.
     */
    public function destroy(AssetGroup $group): RedirectResponse
    {
        if ($group->hasReferencingRecords()) {
            throw ValidationException::withMessages([
                'delete' => $group->deleteBlockedMessage(),
            ]);
        }

        $group->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Golongan dihapus.'),
        ]);

        return to_route('classifications.groups.index');
    }
}
