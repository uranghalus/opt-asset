<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\AssetSubClusterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sub Kelompok — the fourth and deepest classification chain level
 * (rules §1.3).
 *
 * Belongs to exactly one Kelompok; Items attach here. Its `code` locks
 * once an Item references it (ADR-0001).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $asset_cluster_id
 * @property string $code
 * @property string $name
 */
class AssetSubCluster extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AssetSubClusterFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The Kelompok this Sub Kelompok is classified under.
     *
     * @return BelongsTo<AssetCluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(AssetCluster::class, 'asset_cluster_id');
    }

    /**
     * The Items classified under this Sub Kelompok.
     *
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
