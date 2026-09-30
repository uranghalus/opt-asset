<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetCluster;
use App\Models\AssetSubCluster;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SubClusterStoreRequest extends FormRequest
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
     * cluster must exist INSIDE the acting tenant (watchpoint from #30: the
     * DB FK does not prevent cross-tenant parent references, so the lookup
     * goes through the tenant-scoped model query and fails closed — rules
     * §1.1).
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_cluster_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! AssetCluster::query()->whereKey($value)->exists()) {
                        $fail(__('Kelompok tidak ditemukan di unit usaha ini.'));
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (AssetSubCluster::query()->where('code', $value)->exists()) {
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
            'asset_cluster_id' => 'kelompok',
            'code' => 'kode sub kelompok',
            'name' => 'nama sub kelompok',
        ];
    }
}
