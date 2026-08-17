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
        'student_name',
    ];

    public function __construct(private readonly MasterlistVerificationService $verifier) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(string $path): array
    {
        $rows = $this->readRows($path);
        $headers = $rows['headers'];
        $dataRows = $rows['rows'];
        $missingColumns = array_values(array_diff(self::REQUIRED_COLUMNS, $headers));

        $previewRows = collect($dataRows)
            ->map(function (array $row, int $index) use ($missingColumns): array {
                $fieldErrors = [];

                foreach (self::REQUIRED_COLUMNS as $column) {
                    if (trim((string) ($row[$column] ?? '')) === '') {
                        $fieldErrors[] = $this->label($column).' is required.';
                    }
                }

                $errors = $fieldErrors;

                if ($missingColumns !== []) {
                    $errors[] = 'CSV is missing required columns.';
                }

                return [
                    'row_number' => $index + 2,
                    'student_name' => trim((string) ($row['student_name'] ?? '')),
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

        $masterlist = DB::transaction(function () use ($agency, $storedPath, $originalFileName, $preview): ScholarshipMasterlist {
            $masterlist = $agency->masterlists()->create([
                'file_name' => $originalFileName,
                'file_path' => $storedPath,
                'status' => 'uploaded',
                'total_records' => $preview['total_records'],
                'uploaded_at' => now(),
            ]);

            foreach ($preview['rows'] as $row) {
                $masterlist->records()->create([
                    'student_name' => $row['student_name'] ?: null,
                    'verification_status' => 'pending',
                    'remarks' => $row['errors'] !== [] ? implode(' ', $row['errors']) : null,
                ]);
            }

            return $masterlist;
        });

        $this->verifier->verify($masterlist);

        return $masterlist->refresh();
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
