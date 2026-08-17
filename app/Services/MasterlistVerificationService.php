<?php

namespace App\Services;

use App\Models\RegistrarStudent;
use App\Models\ScholarshipMasterlist;
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
                    'matched_student_id' => null,
                    'registrar_student_id' => $verifiedRecord['matched_student_id'] ?? null,
                    'campus_id' => $verifiedRecord['campus_id'] ?? null,
                    'verification_status' => $verifiedRecord['status'],
                    'remarks' => $verifiedRecord['remarks'] ?? null,
                ]);
            }

            $summary = $response['summary'] ?? [];

            $masterlist->update([
                'status' => 'verified',
                'total_records' => $summary['total_records'] ?? $masterlist->records()->count(),
                'enrolled_count' => $summary['enrolled_count'] ?? 0,
                'no_cor_printed_count' => $summary['no_cor_printed_count'] ?? 0,
                'unenrolled_count' => $summary['unenrolled_count'] ?? 0,
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
        $registrarStudents = RegistrarStudent::query()
            ->oldest('id')
            ->get()
            ->map(fn (RegistrarStudent $student): array => [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'campus_id' => $student->campus_id,
                'enrollment_status' => $student->enrollment_status,
                'cor_printed' => $student->cor_printed,
            ]);

        return [
            'records' => $masterlist->records()
                ->oldest('id')
                ->get()
                ->map(fn ($record): array => [
                    'row_id' => $record->id,
                    'student_name' => $record->student_name,
                ])
                ->values()
                ->all(),
            'registrar_students' => $registrarStudents
                ->values()
                ->all(),
        ];
    }
}
