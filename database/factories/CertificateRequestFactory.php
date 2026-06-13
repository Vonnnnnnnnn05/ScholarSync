<?php

namespace Database\Factories;

use App\Enums\CertificateRequestStatus;
use App\Models\CertificateRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CertificateRequest>
 */
class CertificateRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'purpose' => 'For scholarship application verification.',
            'official_receipt' => 'certificate-requests/official-receipts/sample-receipt.pdf',
            'status' => CertificateRequestStatus::Pending,
            'remarks' => null,
        ];
    }

    public function status(CertificateRequestStatus|string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status instanceof CertificateRequestStatus ? $status : CertificateRequestStatus::from($status),
        ]);
    }
}
