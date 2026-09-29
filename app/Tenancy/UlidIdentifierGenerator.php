<?php

namespace App\Tenancy;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

/**
 * Generates ULID tenant identifiers for stancl/tenancy (binding decision
 * 2026-09-25): sortable by creation time, lexicographically ordered, and
 * URL-safe — unlike the package default UUIDv4.
 *
 * The package calls this statically through the contract, hence the static
 * signature.
 */
class UlidIdentifierGenerator implements UniqueIdentifierGenerator
{
    /**
     * Generate a unique tenant identifier.
     *
     * @param  object  $resource  the tenant model being created (unused; part
     *                            of the package contract)
     */
    public static function generate($resource): string
    {
        return (string) Str::ulid();
    }
}
