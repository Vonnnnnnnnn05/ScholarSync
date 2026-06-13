<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ScholarshipMasterlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarshipMasterlist>
 */
class ScholarshipMasterlistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'file_name' => 'masterlist.csv',
            'file_path' => 'masterlists/uploads/masterlist.csv',
            'status' => 'uploaded',
            'total_records' => 0,
            'enrolled_count' => 0,
            'unenrolled_count' => 0,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'uploaded_at' => now(),
        ];
    }
}
