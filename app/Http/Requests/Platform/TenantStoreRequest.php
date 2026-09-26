<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a tenant (platform admin surface).
 */
class TenantStoreRequest extends FormRequest
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
     * Validation rules.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:64',
                // URL-safe identifier: letters, digits, dash, underscore.
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('tenants', 'code'),
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
