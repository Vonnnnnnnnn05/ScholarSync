<?php

namespace App\Services;

use App\Models\RegistrarStudent;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MasterlistVerificationService
{
    /**
     * @return array<string, mixed>|null
     */
    public function verify(ScholarshipMasterlist $masterlist): ?array
    {
        $response = $this->requestVerification($masterlist);

        if ($response === null) {
            return null;
        }

        return DB::transaction(function () use ($masterlist, $response): array {
            $recordsById = $masterlist->records()->get()->keyBy('id');

            foreach ($response['records'] ?? [] as $verifiedRecord) {
                $record = $recordsById->get((int) ($verifiedRecord['row_id'] ?? 0));

                if ($record === null) {
                    continue;
                }

                $record->update([
                    'matched_student_id' => $verifiedRecord['matched_student_id'] ?? null,
                    'verification_status' => $verifiedRecord['status'],
                    'eligibility_status' => $verifiedRecord['eligibility_status'] ?? ($verifiedRecord['status'] === 'enrolled' ? 'qualified' : 'unqualified'),
                    'remarks' => $verifiedRecord['remarks'] ?? null,
                ]);
            }

            $summary = $response['summary'] ?? [];

            $masterlist->update([
                'status' => 'verified',
                'total_records' => $summary['total_records'] ?? $masterlist->records()->count(),
                'enrolled_count' => $summary['enrolled_count'] ?? 0,
                'unenrolled_count' => $summary['unenrolled_count'] ?? 0,
                'duplicate_count' => $summary['duplicate_count'] ?? 0,
                'invalid_count' => $summary['invalid_count'] ?? 0,
                'qualified_count' => $summary['qualified_count'] ?? 0,
                'unqualified_count' => $summary['unqualified_count'] ?? 0,
                'validated_at' => now(),
            ]);

            return $response;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestVerification(ScholarshipMasterlist $masterlist): ?array
    {
        $baseUrl = rtrim((string) config('services.masterlist_verifier.url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            return Http::timeout((int) config('services.masterlist_verifier.timeout', 10))
                ->acceptJson()
                ->post($baseUrl.'/verify-masterlist', $this->payload($masterlist))
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException) {
            return null;
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function payload(ScholarshipMasterlist $masterlist): array
    {
        $enrolledStudents = RegistrarStudent::query()
            ->where('enrollment_status', 'enrolled')
            ->oldest('id')
            ->get()
            ->map(function (RegistrarStudent $student): array {
                $studentProfileId = Student::query()
                    ->where('student_id_number', $student->student_id_number)
                    ->value('id');

                return [
                    'id' => $studentProfileId ?: $student->id,
                    'student_id_number' => $student->student_id_number,
                    'student_name' => $student->student_name,
                ];
            });

        if ($enrolledStudents->isEmpty()) {
            $enrolledStudents = Student::query()
                ->where('status', 'active')
                ->oldest('id')
                ->get()
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                    'student_name' => $student->fullName(),
                ]);
        }

        return [
            'records' => $masterlist->records()
                ->oldest('id')
                ->get()
                ->map(fn ($record): array => [
                    'row_id' => $record->id,
                    'student_id_number' => $record->student_id_number,
                    'student_name' => $record->student_name,
                    'scholarship_program' => $record->scholarship_program,
                    'fund_source' => $record->fund_source,
                ])
                ->values()
                ->all(),
            'enrolled_students' => $enrolledStudents
                ->values()
                ->all(),
        ];
    }
}
