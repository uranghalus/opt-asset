<?php

namespace App\Http\Requests\Classification;

use App\Models\AssetGroup;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class GroupStoreRequest extends FormRequest
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
     * Validation rules — the code is unique per tenant, checked through the
     * tenant-scoped model query so the fail-closed scope decides (rules
     * §1.1), and the name must not be empty.
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
                    if (AssetGroup::query()->where('code', $value)->exists()) {
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
            'code' => 'kode golongan',
            'name' => 'nama golongan',
        ];
    }
}
