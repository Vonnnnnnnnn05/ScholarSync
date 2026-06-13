<?php

namespace Database\Factories;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipRequirement>
 */
class ScholarshipRequirementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => ScholarshipApplication::factory(),
            'requirement_name' => 'Latest Grades',
            'file_path' => 'scholarship-renewals/sample.pdf',
            'status' => 'submitted',
            'remarks' => null,
        ];
    }
}
