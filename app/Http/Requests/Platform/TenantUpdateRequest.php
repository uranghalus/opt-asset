<?php

namespace App\Http\Requests\Platform;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for updating a tenant (platform admin surface).
 */
class TenantUpdateRequest extends FormRequest
{
    /**
     * Authorization: the route is already gated by EnsurePlatformAdmin; the
     * request adds no per-user rules.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules. The code is unique except for the tenant being
     * edited (unique-except-self).
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('tenants', 'code')->ignore(
                    $tenant instanceof Tenant ? $tenant->getKey() : null,
                ),
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
            'code' => 'tenant code',
            'name' => 'tenant name',
        ];
    }
}
