<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetCluster;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ClusterUpdateRequest extends FormRequest
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
     * Validation rules — the code stays unique per tenant (excluding the
     * record being updated), checked through the tenant-scoped model query
     * so the fail-closed scope decides (rules §1.1). The parent category is
     * never editable; only the code and name travel here.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $cluster = $this->route('cluster');

                    $query = AssetCluster::query()->where('code', $value);

                    if ($cluster instanceof AssetCluster) {
                        $query->whereKeyNot($cluster->getKey());
                    }

                    if ($query->exists()) {
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
            'code' => 'kode kelompok',
            'name' => 'nama kelompok',
        ];
    }
}
