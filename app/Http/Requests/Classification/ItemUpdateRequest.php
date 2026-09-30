<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetSubCluster;
use App\Models\Item;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ItemUpdateRequest extends FormRequest
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
     * Validation rules — the item name stays unique per tenant (excluding
     * the record being updated), checked through the tenant-scoped model
     * query so the fail-closed scope decides (rules §1.1). The sub-cluster
     * stays optional and validated inside the acting tenant the same way.
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
                    $item = $this->route('item');

                    $query = Item::query()->where('name', $value);

                    if ($item instanceof Item) {
                        $query->whereKeyNot($item->getKey());
                    }

                    if ($query->exists()) {
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
