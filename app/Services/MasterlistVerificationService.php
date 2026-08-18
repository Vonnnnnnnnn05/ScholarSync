<?php

namespace App\Services;

use App\Models\MasterlistRecord;
use App\Models\MasterlistRecordVerification;
use App\Models\MasterlistVerificationRun;
use App\Models\RegistrarStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MasterlistVerificationService
{
    /** @param array<int, int> $recordIds */
    public function verifyChunk(MasterlistVerificationRun $run, array $recordIds): array
    {
        if (count($recordIds) > 500) {
            throw ValidationException::withMessages(['records' => 'A verification chunk cannot exceed 500 records.']);
        }

        $batch = $run->campusBatch;
        $records = $batch->records()->whereKey($recordIds)->oldest('id')->get();
        if ($records->count() !== count(array_unique($recordIds))) {
            throw ValidationException::withMessages(['records' => 'The chunk contains records outside this campus batch.']);
        }

        $registrarStudents = RegistrarStudent::query()->where('campus_id', $batch->campus_id)->oldest('id')->get();
        $response = Http::timeout((int) config('services.masterlist_verifier.timeout', 30))
            ->retry(2, 250, throw: false)
            ->acceptJson()
            ->post(rtrim((string) config('services.masterlist_verifier.url'), '/').'/verify-masterlist', [
                'records' => $records->map(fn (MasterlistRecord $record) => [
                    'row_id' => $record->id,
                    'student_id_number' => $record->student_id_number,
                    'student_name' => $record->student_name,
                    'campus_id' => $record->campus_id,
                ])->all(),
                'registrar_students' => $registrarStudents->map(fn (RegistrarStudent $student) => [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                    'student_name' => $student->student_name,
                    'campus_id' => $student->campus_id,
                    'enrollment_status' => $student->enrollment_status,
                    'cor_printed' => $student->cor_printed,
                ])->all(),
            ])->throw()->json();

        $results = collect($response['records'] ?? [])->keyBy('row_id');
        if ($results->count() !== $records->count() || $records->contains(fn ($record) => ! $results->has($record->id))) {
            throw ValidationException::withMessages(['response' => 'The verification service returned an incomplete or invalid response.']);
        }

        DB::transaction(function () use ($records, $results, $registrarStudents, $run, $response): void {
            foreach ($records as $record) {
                $result = $results->get($record->id);
                $matched = $registrarStudents->firstWhere('id', $result['matched_student_id'] ?? null);
                $now = now();
                $record->update([
                    'registrar_student_id' => $matched?->id,
                    'match_status' => $result['match_status'],
                    'automatic_enrollment_status' => $result['enrollment_status'],
                    'automatic_cor_status' => $result['cor_status'],
                    'automatic_qualification_status' => $result['qualification_status'],
                    'final_enrollment_status' => $result['enrollment_status'],
                    'final_cor_status' => $result['cor_status'],
                    'final_qualification_status' => $result['qualification_status'],
                    'automatic_verified_at' => $now,
                    'automatic_result_message' => $result['remarks'] ?? null,
                    'verification_status' => $result['qualification_status'] === 'qualified' ? 'verified' : ($result['qualification_status'] === 'not_qualified' ? 'not_verified' : 'pending'),
                ]);
                MasterlistRecordVerification::updateOrCreate(
                    ['masterlist_verification_run_id' => $run->id, 'masterlist_record_id' => $record->id],
                    [
                        'original_data' => $record->only(['student_id_number', 'student_name', 'campus_id', 'scholarship_program', 'fund_source']),
                        'matched_data' => $matched?->only(['id', 'student_id_number', 'student_name', 'campus_id', 'enrollment_status', 'cor_printed', 'academic_year', 'semester']),
                        'match_status' => $result['match_status'],
                        'enrollment_status' => $result['enrollment_status'],
                        'cor_status' => $result['cor_status'],
                        'qualification_status' => $result['qualification_status'],
                        'result_message' => $result['remarks'] ?? null,
                        'service_version' => $response['service_version'] ?? null,
                        'verified_at' => $now,
                    ]
                );
            }
        });

        return $response;
    }
}
