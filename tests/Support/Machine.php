<?php

namespace Tests\Support;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Throwaway domain model used ONLY by the tenancy test-suite to prove the
 * isolation mechanics every real domain model will inherit.
 *
 * The backing `machines` table is created/dropped per test inside
 * TenantIsolationTest, so production schema stays untouched until T03+
 * introduce the real classification and asset tables.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 */
class Machine extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'machines';

    protected $guarded = [];
}
