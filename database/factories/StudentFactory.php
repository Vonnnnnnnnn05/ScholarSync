<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_id_number' => fake()->unique()->numerify('SKSU-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'course' => 'BS Information Technology',
            'year_level' => '4th Year',
            'campus' => 'ACCESS Campus',
            'contact_number' => fake()->numerify('09#########'),
            'status' => 'active',
        ];
    }
}
