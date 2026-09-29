<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\AssetGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Golongan — the top level of the classification chain (rules §1.3).
 *
 * The chain `code` is unique per tenant and will be concatenated in chain
 * order to build `kode_asset` (T04). It is immutable once a Kategori
 * references it (ADR-0001) — the storage layer blocks re-parenting and
 * deletion; the HTTP layer must also refuse code edits on referenced rows.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 */
class AssetGroup extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AssetGroupFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The Kategori records classified under this Golongan.
     *
     * @return HasMany<AssetCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }
}
