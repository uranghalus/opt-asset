<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for tenant status transitions (platform admin surface).
 */
class TenantTransitionRequest extends FormRequest
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
     * Validation rules: only known statuses are accepted.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
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
            'status' => 'tenant status',
        ];
    }
}
