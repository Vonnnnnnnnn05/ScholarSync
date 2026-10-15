<?php

namespace App\Services;

use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\StagedGranteeRecord;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GranteeVerificationService
{
    /**
     * Verify a chunk of records in a GranteeUploadBatch.
     *
     * @param GranteeUploadBatch $batch
     * @param array<int, int>|null $recordIds Specific record IDs to verify, or null for all pending in batch
     * @return array{
     *     total_verified: int,
     *     green_count: int,
     *     amber_count: int,
     *     red_count: int
     * }
     * @throws ValidationException
     */
    public function verifyBatchOrChunk(GranteeUploadBatch $batch, ?array $recordIds = null): array
    {
        $batch->update(['status' => 'verifying']);

        $query = $batch->stagedRecords();
        if (!empty($recordIds)) {
            $query->whereIn('id', $recordIds);
        } else {
            $query->where('verification_status', 'PENDING');
        }

        $records = $query->take(500)->get();
        if ($records->isEmpty()) {
            $batch->refreshCounters();
            if ($batch->pending_count === 0) {
                $batch->update(['status' => 'verified', 'verified_at' => now()]);
            }
            return ['total_verified' => 0, 'green_count' => 0, 'amber_count' => 0, 'red_count' => 0];
        }

        // Fetch registrar students
        $registrarStudents = RegistrarStudent::query()
            ->select(['id', 'student_id_number', 'student_name', 'campus_id', 'enrollment_status', 'cor_printed', 'course', 'year_level'])
            ->get();

        // Fetch existing grantees for double-grant check
        $existingGranteeStudentIds = Student::query()
            ->where('is_scholarship_grantee', true)
            ->pluck('id')
            ->all();

        $baseUrl = rtrim((string) config('services.masterlist_verifier.url', 'http://127.0.0.1:8001'), '/');
        $timeout = (int) config('services.masterlist_verifier.timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->retry(2, 250, throw: false)
                ->acceptJson()
                ->post("{$baseUrl}/verify-grantees", [
                    'records' => $records->map(fn (StagedGranteeRecord $r) => [
                        'row_id' => $r->id,
                        'student_id' => $r->student_id,
                        'student_id_number' => $r->student_id,
                        'student_name' => $r->student_name ?: $r->displayName(),
                        'first_name' => $r->first_name,
                        'middle_name' => $r->middle_name,
                        'last_name' => $r->last_name,
                        'campus_id' => $r->campus_id ?? $batch->campus_id,
                        'scholarship_program' => $r->scholarship_program ?? $r->scholarship_name,
                        'source_pdf_name' => $r->source_pdf_name,
                        'is_incomplete_name' => empty($r->first_name) && !empty($r->last_name),
                    ])->all(),
                    'registrar_students' => $registrarStudents->map(fn (RegistrarStudent $s) => [
                        'id' => $s->id,
                        'student_id_number' => $s->student_id_number,
                        'student_name' => $s->student_name,
                        'campus_id' => $s->campus_id,
                        'campus' => $s->campus ?? $s->campusRef?->name,
                        'enrollment_status' => $s->enrollment_status ?? 'enrolled',
                        'cor_printed' => (bool) $s->cor_printed,
                        'course' => $s->course,
                        'year_level' => $s->year_level,
                    ])->all(),
                    'existing_grantee_student_ids' => $existingGranteeStudentIds,
                ]);
        } catch (\Exception $e) {
            $batch->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'verification' => 'Failed to connect to verification microservice: ' . $e->getMessage(),
            ]);
        }

        if ($response->failed()) {
            $batch->update(['status' => 'failed']);
            throw ValidationException::withMessages([
                'verification' => 'Verification engine returned an error: ' . ($response->json()['detail'] ?? $response->body()),
            ]);
        }

        $responseData = $response->json();
        $verifiedRecords = collect($responseData['records'] ?? [])->keyBy('row_id');

        DB::transaction(function () use ($records, $verifiedRecords) {
            foreach ($records as $record) {
                $v = $verifiedRecords->get($record->id);
                if (!$v) {
                    continue;
                }

                $matchedSisId = $v['matched_registrar_student_id'] ?? null;
                $matchedStudentId = null;

                if ($matchedSisId) {
                    // Try to link to matching Student in the users/students table
                    $sis = RegistrarStudent::find($matchedSisId);
                    if ($sis) {
                        $matchedStudent = Student::query()
                            ->where('student_id_number', $sis->student_id_number)
                            ->first();
                        $matchedStudentId = $matchedStudent?->id;
                    }
                }

                $record->update([
                    'verification_status' => $v['verification_status'],
                    'confidence_score' => $v['confidence_score'] ?? null,
                    'matched_sis_student_id' => $matchedSisId,
                    'matched_student_id' => $matchedStudentId,
                    'conflict_reason' => $v['conflict_reason'] ?? null,
                    'candidate_matches' => $v['candidate_matches'] ?? null,
                    'campus_id' => $v['resolved_campus_id'] ?? $sis?->campus_id ?? $record->campus_id,
                    'campus' => $v['resolved_campus'] ?? $sis?->campus ?? $sis?->campusRef?->name ?? $record->campus,
                    'program' => $v['resolved_program'] ?? $sis?->course ?? $record->program,
                    'enrollment_status' => $v['enrollment_status'] ?? $record->enrollment_status,
                    'remarks' => $v['remarks'] ?? $record->remarks,
                ]);
            }
        });

        // Recalculate counters
        $batch->refreshCounters();

        if ($batch->pending_count === 0) {
            $batch->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);
        }

        return [
            'total_verified' => $records->count(),
            'green_count' => $batch->green_count,
            'amber_count' => $batch->amber_count,
            'red_count' => $batch->red_count,
        ];
    }
}
