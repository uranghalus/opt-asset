<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\AssetCluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCluster>
 */
class AssetClusterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Place the cluster under the given Kategori (required — the chain has
     * no orphaned levels).
     */
    public function forCategory(AssetCategory $category): static
    {
        return $this->for($category, 'category');
    }
}
