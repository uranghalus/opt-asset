<?php

namespace Database\Factories;

use App\Models\AssetCluster;
use App\Models\AssetSubCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetSubCluster>
 */
class AssetSubClusterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Place the sub cluster under the given Kelompok (required — the chain
     * has no orphaned levels).
     */
    public function forCluster(AssetCluster $cluster): static
    {
        return $this->for($cluster, 'cluster');
    }
}
