<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UrlMapping>
 */
class UrlMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "short_url" => fake()->regexify("[a-zA-Z0-9]{7}"),
            "long_url" => fake()->url() . rand(),
        ];
    }
}
