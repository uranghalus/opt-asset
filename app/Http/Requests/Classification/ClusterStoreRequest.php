<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetCategory;
use App\Models\AssetCluster;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ClusterStoreRequest extends FormRequest
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
     * Validation rules — the code is unique per tenant and the parent
     * category must exist INSIDE the acting tenant (watchpoint from #30:
     * the DB FK does not prevent cross-tenant parent references, so the
     * lookup goes through the tenant-scoped model query and fails closed —
     * rules §1.1).
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_category_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! AssetCategory::query()->whereKey($value)->exists()) {
                        $fail(__('Kategori tidak ditemukan di unit usaha ini.'));
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (AssetCluster::query()->where('code', $value)->exists()) {
                        $fail(__('Kode :code sudah digunakan di unit usaha ini.', ['code' => $value]));
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
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
            'asset_category_id' => 'kategori',
            'code' => 'kode kelompok',
            'name' => 'nama kelompok',
        ];
    }
}
