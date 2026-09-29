<?php

namespace Database\Factories;

use App\Models\AssetSubCluster;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Default is unclassified — the import auto-create shape (rules §1.3).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Classify the item under the given Sub Kelompok.
     */
    public function forSubCluster(AssetSubCluster $subCluster): static
    {
        return $this->for($subCluster, 'subCluster');
    }

    /**
     * Indicate the item carries no classification at all (explicit — the
     * default state already omits one).
     */
    public function unclassified(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_sub_cluster_id' => null,
        ]);
    }
}
