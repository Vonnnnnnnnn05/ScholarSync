<?php

namespace Database\Factories;

use App\Models\ScholarshipProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipProgram>
 */
class ScholarshipProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true).' Scholarship',
            'fund_source' => fake()->randomElement(['CHED', 'LGU', 'SKSU Foundation']),
            'agency_name' => fake()->company(),
            'status' => 'active',
        ];
    }
}
