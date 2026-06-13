<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\ScholarshipMasterlist;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MasterlistCsvService
{
    public const REQUIRED_COLUMNS = [
        'student_id_number',
        'student_name',
        'scholarship_program',
        'fund_source',
    ];

    /**
     * @return array<string, mixed>
     */
    public function preview(string $path): array
    {
        $rows = $this->readRows($path);
        $headers = $rows['headers'];
        $dataRows = $rows['rows'];
        $missingColumns = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        $studentIdCounts = [];

        foreach ($dataRows as $row) {
            $studentId = trim((string) ($row['student_id_number'] ?? ''));

            if ($studentId !== '') {
                $studentIdCounts[$studentId] = ($studentIdCounts[$studentId] ?? 0) + 1;
            }
        }

        $previewRows = collect($dataRows)
            ->map(function (array $row, int $index) use ($studentIdCounts, $missingColumns): array {
                $fieldErrors = [];

                foreach (self::REQUIRED_COLUMNS as $column) {
                    if (trim((string) ($row[$column] ?? '')) === '') {
                        $fieldErrors[] = $this->label($column).' is required.';
                    }
                }

                $studentId = trim((string) ($row['student_id_number'] ?? ''));
                $isDuplicate = $studentId !== '' && ($studentIdCounts[$studentId] ?? 0) > 1;
                $errors = $fieldErrors;

                if ($isDuplicate) {
                    $errors[] = 'Duplicate student ID in uploaded file.';
                }

                if ($missingColumns !== []) {
                    $errors[] = 'CSV is missing required columns.';
                }

                return [
                    'row_number' => $index + 2,
                    'student_id_number' => $studentId,
                    'student_name' => trim((string) ($row['student_name'] ?? '')),
                    'scholarship_program' => trim((string) ($row['scholarship_program'] ?? '')),
                    'fund_source' => trim((string) ($row['fund_source'] ?? '')),
                    'is_duplicate' => $isDuplicate,
                    'is_invalid' => $fieldErrors !== [] || $missingColumns !== [],
                    'errors' => $errors,
                ];
            })
            ->values()
            ->all();

        return [
            'headers' => $headers,
            'missing_columns' => $missingColumns,
            'rows' => $previewRows,
            'total_records' => count($previewRows),
            'duplicate_count' => collect($previewRows)->where('is_duplicate', true)->count(),
            'invalid_count' => collect($previewRows)->where('is_invalid', true)->count(),
        ];
    }

    public function storeTemporary(UploadedFile $file): string
    {
        return $file->store('masterlists/tmp', 'local');
    }

    public function import(Agency $agency, string $temporaryPath, string $originalFileName): ScholarshipMasterlist
    {
        abort_unless(Storage::disk('local')->exists($temporaryPath), 404);

        $preview = $this->preview($temporaryPath);
        $storedPath = 'masterlists/uploads/'.basename($temporaryPath);

        Storage::disk('local')->copy($temporaryPath, $storedPath);

        return DB::transaction(function () use ($agency, $storedPath, $originalFileName, $preview): ScholarshipMasterlist {
            $masterlist = $agency->masterlists()->create([
                'file_name' => $originalFileName,
                'file_path' => $storedPath,
                'status' => 'uploaded',
                'total_records' => $preview['total_records'],
                'duplicate_count' => $preview['duplicate_count'],
                'invalid_count' => $preview['invalid_count'],
                'uploaded_at' => now(),
            ]);

            foreach ($preview['rows'] as $row) {
                $verificationStatus = match (true) {
                    $row['is_invalid'] && $row['is_duplicate'] => 'duplicate',
                    $row['is_duplicate'] => 'duplicate',
                    $row['is_invalid'] => 'invalid',
                    default => 'pending',
                };

                $masterlist->records()->create([
                    'student_id_number' => $row['student_id_number'] ?: null,
                    'student_name' => $row['student_name'] ?: null,
                    'scholarship_program' => $row['scholarship_program'] ?: null,
                    'fund_source' => $row['fund_source'] ?: null,
                    'verification_status' => $verificationStatus,
                    'remarks' => $row['errors'] !== [] ? implode(' ', $row['errors']) : null,
                ]);
            }

            return $masterlist;
        });
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string|null>>}
     */
    private function readRows(string $path): array
    {
        $absolutePath = Storage::disk('local')->path($path);
        $handle = fopen($absolutePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to read CSV file.');
        }

        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false) {
            fclose($handle);

            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rawHeaders);
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($this->isBlankRow($values)) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = Arr::only($row, self::REQUIRED_COLUMNS);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function normalizeHeader(string $header): string
    {
        return str($header)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function isBlankRow(array $values): bool
    {
        return collect($values)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function label(string $column): string
    {
        return str($column)->replace('_', ' ')->title()->toString();
    }
}
