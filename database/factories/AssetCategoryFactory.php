<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\AssetGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The code lexifies to two letters: wide enough for several children
     * under one parent while keeping the two-tenant uniqueness proofs
     * friction-free.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Place the category under the given Golongan (required — the chain
     * has no orphaned levels).
     */
    public function forGroup(AssetGroup $group): static
    {
        return $this->for($group, 'group');
    }
}
