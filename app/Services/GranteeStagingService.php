<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\GranteeUploadBatch;
use App\Models\StagedGranteeRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GranteeStagingService
{
    /**
     * Stage an uploaded grantee file and parsed records into the database.
     *
     * @param User $user
     * @param UploadedFile $file
     * @param array{
     *     agency_name: string,
     *     scholarship_name: string,
     *     academic_year: string,
     *     semester: string,
     *     file_type: string,
     *     raw_headers: array<int, string>,
     *     rows: array<int, array<string, mixed>>
     * } $data
     * @return GranteeUploadBatch
     */
    public function stageBatch(User $user, UploadedFile|array $files, array $data): GranteeUploadBatch
    {
        $fileList = is_array($files) ? $files : [$files];
        $storedPaths = [];
        $fileNames = [];

        foreach ($fileList as $f) {
            $storedPaths[] = $f->store('grantees/uploads', 'local');
            $fileNames[] = $f->getClientOriginalName();
        }

        $primaryName = implode(', ', $fileNames);
        $primaryPath = implode(';', $storedPaths);

        // Cache campus lookup by name
        $campuses = Campus::all()->keyBy(fn ($c) => strtolower(trim($c->name)));

        return DB::transaction(function () use ($user, $fileNames, $primaryName, $primaryPath, $data, $campuses) {
            $batch = GranteeUploadBatch::create([
                'uploaded_by' => $user->id,
                'campus_id' => $data['campus_id'] ?? null,
                'agency_name' => $data['agency_name'],
                'scholarship_name' => !empty($data['scholarship_name']) ? $data['scholarship_name'] : 'Auto-Detected',
                'academic_year' => $data['academic_year'],
                'semester' => $data['semester'],
                'original_file_name' => $primaryName,
                'uploaded_filenames' => $fileNames,
                'stored_file_path' => $primaryPath,
                'file_type' => $data['file_type'] ?? 'pdf',
                'raw_headers' => $data['raw_headers'] ?? [],
                'total_rows' => count($data['rows']),
                'pending_count' => count($data['rows']),
                'green_count' => 0,
                'amber_count' => 0,
                'red_count' => 0,
                'status' => 'staged',
            ]);

            $recordsToInsert = [];
            $now = now();

            foreach ($data['rows'] as $index => $row) {
                $rowNumber = $row['row_number'] ?? ($index + 1);
                $campusName = isset($row['campus']) ? trim((string) $row['campus']) : null;
                $campusId = null;

                if ($campusName) {
                    $lowerCampus = strtolower($campusName);
                    // Match full name or partial campus name
                    foreach ($campuses as $key => $campus) {
                        if ($key === $lowerCampus || str_contains($lowerCampus, $key) || str_contains($key, $lowerCampus)) {
                            $campusId = $campus->id;
                            break;
                        }
                    }
                } elseif (!empty($data['campus_id'])) {
                    $campusId = (int) $data['campus_id'];
                }

                $missingFlags = [];
                if (empty($row['student_id'])) {
                    $missingFlags[] = 'student_id';
                }
                if (empty($campusName) && empty($campusId)) {
                    $missingFlags[] = 'campus';
                }
                if (empty($row['program'])) {
                    $missingFlags[] = 'program';
                }

                $prog = $row['scholarship_program'] ?? ($row['scholarship_name'] ?? ($data['scholarship_name'] ?? 'Auto-Detected'));

                $status = $row['verification_status'] ?? 'PENDING';
                $confScore = ($status === 'GREEN_VERIFIED') ? 1.0 : ($row['confidence_score'] ?? null);

                $recordsToInsert[] = [
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'source_pdf_name' => $row['source_pdf_name'] ?? ($row['source_file'] ?? ($fileNames[0] ?? null)),
                    'raw_payload' => json_encode($row),
                    'first_name' => $row['first_name'] ?? null,
                    'middle_name' => $row['middle_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'student_name' => $row['student_name'] ?? null,
                    'raw_agency_name' => $row['raw_agency_name'] ?? ($row['student_name'] ?? null),
                    'student_id' => $row['student_id'] ?? null,
                    'campus' => $campusName,
                    'campus_id' => $campusId,
                    'program' => $row['program'] ?? null,
                    'scholarship_name' => $prog,
                    'scholarship_program' => $prog,
                    'missing_fields_flag' => json_encode($missingFlags),
                    'verification_status' => $status,
                    'confidence_score' => $confScore,
                    'matched_sis_student_id' => $row['matched_sis_student_id'] ?? null,
                    'matched_student_id' => $row['matched_student_id'] ?? null,
                    'enrollment_status' => $row['enrollment_status'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($recordsToInsert) >= 500) {
                    StagedGranteeRecord::insert($recordsToInsert);
                    $recordsToInsert = [];
                }
            }

            if (!empty($recordsToInsert)) {
                StagedGranteeRecord::insert($recordsToInsert);
            }

            $batch->refreshCounters();

            return $batch;
        });
    }
}
