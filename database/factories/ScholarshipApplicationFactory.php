<?php

namespace Database\Factories;

use App\Enums\ScholarshipApplicationStatus;
use App\Models\ScholarshipApplication;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipApplication>
 */
class ScholarshipApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'scholarship_program' => 'Continuing Merit Scholarship',
            'fund_source' => 'CHED',
            'status' => ScholarshipApplicationStatus::Submitted,
            'remarks' => null,
            'evaluated_by' => null,
            'evaluated_at' => null,
        ];
    }
}
