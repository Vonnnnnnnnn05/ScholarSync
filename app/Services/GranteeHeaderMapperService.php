<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class GranteeHeaderMapperService
{
    /**
     * Validate headers and build structured preview data.
     *
     * @param array<string, mixed> $microserviceResult
     * @return array{
     *     file_type: string,
     *     original_headers: array<int, string>,
     *     normalized_headers: array<int, string>,
     *     missing_fields: array<int, string>,
     *     unmapped_headers: array<int, string>,
     *     total_rows: int,
     *     preview_rows: array<int, array<string, mixed>>,
     *     all_rows: array<int, array<string, mixed>>,
     *     parse_warnings: array<int, string>,
     *     has_student_id: bool,
     *     has_campus: bool,
     *     has_program: bool
     * }
     * @throws ValidationException
     */
    public function validateAndStructure(array $microserviceResult): array
    {
        $normalizedHeaders = $microserviceResult['normalized_headers'] ?? [];
        $rows = $microserviceResult['rows'] ?? [];

        // Ensure at least one name column exists
        $hasName = in_array('student_name', $normalizedHeaders, true)
            || (in_array('first_name', $normalizedHeaders, true) && in_array('last_name', $normalizedHeaders, true));

        if (!$hasName) {
            // Check if any row has student_name populated
            $anyRowHasName = collect($rows)->contains(function ($r) {
                return !empty($r['student_name']) || (!empty($r['first_name']) && !empty($r['last_name']));
            });

            if (!$anyRowHasName) {
                throw ValidationException::withMessages([
                    'file' => 'Could not detect a student name column (e.g., "Student Name", "Beneficiary", "First Name", "Last Name"). Name is required for grantee ingestion.',
                ]);
            }
        }

        $missingFields = $microserviceResult['missing_fields'] ?? [];
        $hasStudentId = in_array('student_id', $normalizedHeaders, true);
        $hasCampus = in_array('campus', $normalizedHeaders, true);
        $hasProgram = in_array('program', $normalizedHeaders, true);

        return [
            'file_type' => $microserviceResult['file_type'] ?? 'unknown',
            'original_headers' => $microserviceResult['original_headers'] ?? [],
            'normalized_headers' => $normalizedHeaders,
            'missing_fields' => $missingFields,
            'unmapped_headers' => $microserviceResult['unmapped_headers'] ?? [],
            'total_rows' => count($rows),
            'preview_rows' => array_slice($rows, 0, 50),
            'all_rows' => $rows,
            'parse_warnings' => $microserviceResult['parse_warnings'] ?? [],
            'has_student_id' => $hasStudentId,
            'has_campus' => $hasCampus,
            'has_program' => $hasProgram,
        ];
    }
}
