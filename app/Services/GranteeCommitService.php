<?php

namespace App\Services;

use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GranteeCommitService
{
    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    /**
     * Finalize and commit a verified grantee batch into the core student system.
     *
     * @param GranteeUploadBatch $batch
     * @param User $committer
     * @return int Number of students flagged as scholarship grantees
     * @throws ValidationException
     */
    public function commitBatch(GranteeUploadBatch $batch, User $committer): int
    {
        // 1. Ensure no unresolved amber rows remain
        $unresolvedCount = $batch->stagedRecords()
            ->where('verification_status', 'AMBER_CONFLICT')
            ->count();

        if ($unresolvedCount > 0) {
            throw ValidationException::withMessages([
                'batch' => "Cannot commit batch: there are {$unresolvedCount} unresolved conflict (amber) records that require Chairman resolution.",
            ]);
        }

        if ($batch->status === 'committed') {
            throw ValidationException::withMessages([
                'batch' => 'This grantee batch has already been committed.',
            ]);
        }

        return DB::transaction(function () use ($batch, $committer) {
            $greenRecords = $batch->stagedRecords()
                ->where('verification_status', 'GREEN_VERIFIED')
                ->get();

            $committedStudentsCount = 0;

            foreach ($greenRecords as $record) {
                $student = null;

                if ($record->matched_student_id) {
                    $student = Student::find($record->matched_student_id);
                }

                if (!$student && $record->matched_sis_student_id) {
                    $sis = RegistrarStudent::find($record->matched_sis_student_id);
                    if ($sis) {
                        $student = Student::where('student_id_number', $sis->student_id_number)->first();
                    }
                }

                if (!$student && $record->student_id) {
                    $student = Student::where('student_id_number', $record->student_id)->first();
                }

                if ($student) {
                    $student->update([
                        'is_scholarship_grantee' => true,
                    ]);
                    $record->update([
                        'matched_student_id' => $student->id,
                    ]);
                    $committedStudentsCount++;
                }
            }

            $batch->update([
                'status' => 'committed',
                'committed_at' => now(),
                'committed_by' => $committer->id,
            ]);

            // Audit Trail
            $this->auditTrail->record(
                'batch_committed',
                $batch,
                [
                    'batch_id' => $batch->id,
                    'scholarship_name' => $batch->scholarship_name,
                    'total_rows' => $batch->total_rows,
                    'green_committed' => $committedStudentsCount,
                    'red_skipped' => $batch->red_count,
                    'committed_by' => $committer->id,
                ]
            );

            return $committedStudentsCount;
        });
    }
}
