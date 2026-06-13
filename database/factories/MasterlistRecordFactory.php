<?php

namespace Database\Factories;

use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterlistRecord>
 */
class MasterlistRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'masterlist_id' => ScholarshipMasterlist::factory(),
            'student_id_number' => fake()->unique()->numerify('SKSU-#####'),
            'student_name' => fake()->name(),
            'scholarship_program' => 'Merit Scholarship',
            'fund_source' => 'CHED',
            'verification_status' => 'pending',
            'coordinator_status' => 'pending',
            'chairman_status' => 'pending',
            'remarks' => null,
        ];
    }
}
