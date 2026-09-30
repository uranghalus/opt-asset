<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\LocksCodeWhenReferenced;
use Database\Factories\AssetClusterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelompok — the third classification chain level (rules §1.3).
 *
 * Belongs to exactly one Kategori; its `code` locks once a Sub Kelompok
 * references it (ADR-0001).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $asset_category_id
 * @property string $code
 * @property string $name
 */
class AssetCluster extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<AssetClusterFactory> */
    use HasFactory;

    use HasUlids;
    use LocksCodeWhenReferenced;

    /**
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The Kategori this Kelompok is classified under.
     *
     * @return BelongsTo<AssetCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /**
     * The Sub Kelompok records classified under this Kelompok.
     *
     * @return HasMany<AssetSubCluster, $this>
     */
    public function subClusters(): HasMany
    {
        return $this->hasMany(AssetSubCluster::class);
    }

    /**
     * The child model classes whose existing records lock this Kelompok's
     * `code` — a Sub Kelompok referencing it (ADR-0001).
     *
     * @return list<class-string<AssetSubCluster>>
     */
    protected function referencingModels(): array
    {
        return [AssetSubCluster::class];
    }
}
