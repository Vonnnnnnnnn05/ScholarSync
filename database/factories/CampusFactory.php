<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campus> */
class CampusFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city().' Campus';

        return ['code' => fake()->unique()->slug(2), 'name' => $name, 'is_active' => true];
    }
}
