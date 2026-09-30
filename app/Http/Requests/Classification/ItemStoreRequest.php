<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetSubCluster;
use App\Models\Item;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
{
    /**
     * Authorization: the route is already gated by the tenant context and
     * the permission:classifications.manage middleware; the request adds no
     * per-user rules.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules — the item name is unique per tenant (items carry no
     * code column) and the sub-cluster is optional (auto-created imports
     * land unclassified). A given sub-cluster must exist INSIDE the acting
     * tenant (watchpoint from #30: the DB FK does not prevent cross-tenant
     * parent references, so the lookup goes through the tenant-scoped model
     * query and fails closed — rules §1.1).
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Item::query()->where('name', $value)->exists()) {
                        $fail(__('Nama item :name sudah digunakan di unit usaha ini.', ['name' => $value]));
                    }
                },
            ],
            'asset_sub_cluster_id' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    if (! AssetSubCluster::query()->whereKey($value)->exists()) {
                        $fail(__('Sub kelompok tidak ditemukan di unit usaha ini.'));
                    }
                },
            ],
        ];
    }

    /**
     * Human-safe attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama item',
            'asset_sub_cluster_id' => 'sub kelompok',
        ];
    }
}
