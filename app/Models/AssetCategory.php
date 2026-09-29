<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\AssetCategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kategori — the second classification chain level (rules §1.3).
 *
 * Belongs to exactly one Golongan; its `code` locks once a Kelompok
 * references it (ADR-0001).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $asset_group_id
 * @property string $code
 * @property string $name
 */
class AssetCategory extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AssetCategoryFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The Golongan this Kategori is classified under.
     *
     * @return BelongsTo<AssetGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AssetGroup::class, 'asset_group_id');
    }

    /**
     * The Kelompok records classified under this Kategori.
     *
     * @return HasMany<AssetCluster, $this>
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(AssetCluster::class);
    }
}
