<?php

namespace Database\Factories;

use App\Models\AssetGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetGroup>
 */
class AssetGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('?')),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
