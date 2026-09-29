<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item — a concrete article assets instantiate, classified under a Sub
 * Kelompok when known.
 *
 * The classification is nullable by design: Excel imports auto-create
 * unknown items without one (rules §1.3 — imports never skip rows). The
 * name is unique per tenant; the sub-cluster FK restricts deletion so a
 * referenced level stays locked (ADR-0001).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $asset_sub_cluster_id
 * @property string $name
 */
class Item extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The Sub Kelompok this Item is classified under, when it has one.
     *
     * @return BelongsTo<AssetSubCluster, $this>
     */
    public function subCluster(): BelongsTo
    {
        return $this->belongsTo(AssetSubCluster::class, 'asset_sub_cluster_id');
    }
}
