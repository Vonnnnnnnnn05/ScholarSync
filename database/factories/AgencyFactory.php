<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->role(UserRole::ScholarshipAgency),
            'agency_name' => fake()->company().' Scholarship Office',
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'contact_number' => fake()->numerify('09#########'),
            'status' => 'active',
        ];
    }
}
