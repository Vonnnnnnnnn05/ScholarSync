<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\RegistrarStudent;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class EnrolledStudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        return view('registrar.enrolled-students.index', [
            'search' => $search,
            'students' => RegistrarStudent::query()
                ->when($request->user()->campus_id, fn ($query, $campusId) => $query->where('campus_id', $campusId))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('student_id_number', 'like', "%{$search}%")
                            ->orWhere('student_name', 'like', "%{$search}%")
                            ->orWhere('course', 'like', "%{$search}%")
                            ->orWhere('campus', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function import(Request $request, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_file' => ['required', 'file', 'extensions:xlsx', 'max:5120'],
        ]);

        try {
            $rows = $this->excelRows($validated['enrollment_file']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['enrollment_file' => $exception->getMessage()])->withInput();
        }

        $requiredColumns = ['student_id_number', 'student_name'];
        $missingColumns = array_values(array_diff($requiredColumns, $rows['headers']));

        if ($missingColumns !== []) {
            return back()
                ->withErrors(['enrollment_file' => 'The Excel file is missing required columns: '.implode(', ', $missingColumns).'.'])
                ->withInput();
        }

        [$imported, $skipped] = DB::transaction(function () use ($rows, $request): array {
            $count = 0;
            $skipped = 0;

            foreach ($rows['rows'] as $row) {
                $studentIdNumber = trim((string) ($row['student_id_number'] ?? ''));
                $studentName = trim((string) ($row['student_name'] ?? ''));

                if ($studentIdNumber === '' || $studentName === '') {
                    $skipped++;

                    continue;
                }

                RegistrarStudent::updateOrCreate(
                    ['student_id_number' => $studentIdNumber, 'campus_id' => $request->user()->campus_id],
                    [
                        'student_name' => $studentName,
                        'course' => $this->nullableValue($row['course'] ?? null),
                        'year_level' => $this->nullableValue($row['year_level'] ?? null),
                        'campus' => $request->user()->campus?->name ?? $this->nullableValue($row['campus'] ?? null),
                        'enrollment_status' => $this->validEnrollmentStatus($row['enrollment_status'] ?? null),
                        'cor_printed' => $this->printedCor($row['cor_printed'] ?? null),
                        'academic_year' => $this->nullableValue($row['academic_year'] ?? null),
                        'semester' => $this->nullableValue($row['semester'] ?? null),
                    ],
                );

                $count++;
            }

            return [$count, $skipped];
        });

        $audit->record('registrar_students_imported', new RegistrarStudent, [
            'imported_count' => $imported,
            'skipped_count' => $skipped,
        ], $request);

        return redirect()
            ->route('registrar.enrolled-students.index')
            ->with('status', "{$imported} enrollment record(s) imported.".($skipped ? " {$skipped} incomplete row(s) skipped." : ''));
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string|null>>}
     */
    private function excelRows(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $workbook = $reader->load($file->getRealPath());
        } catch (Throwable $exception) {
            throw new RuntimeException('The uploaded file could not be read as an Excel workbook.', previous: $exception);
        }

        $requiredColumns = ['student_id_number', 'student_name'];
        $allowedColumns = ['student_id_number', 'student_name', 'course', 'year_level', 'campus', 'enrollment_status', 'cor_printed', 'academic_year', 'semester'];
        $fallbackHeaders = [];

        foreach ($workbook->getAllSheets() as $sheet) {
            $values = $sheet->toArray(null, true, true, false);

            foreach (array_slice($values, 0, 25, true) as $headerIndex => $headerRow) {
                $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headerRow);
                $fallbackHeaders = $fallbackHeaders ?: $headers;

                if (array_diff($requiredColumns, $headers) !== []) {
                    continue;
                }

                $rows = [];
                foreach (array_slice($values, $headerIndex + 1) as $valueRow) {
                    if (collect($valueRow)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                        continue;
                    }

                    $row = [];
                    foreach ($headers as $index => $header) {
                        $row[$header] = isset($valueRow[$index]) ? (string) $valueRow[$index] : null;
                    }
                    $rows[] = Arr::only($row, $allowedColumns);
                }

                $workbook->disconnectWorksheets();

                return ['headers' => $headers, 'rows' => $rows];
            }
        }

        $workbook->disconnectWorksheets();

        return ['headers' => $fallbackHeaders, 'rows' => []];
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

    private function nullableValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function validEnrollmentStatus(?string $status): string
    {
        $status = str($status ?: 'enrolled')->trim()->lower()->replace([' ', '-'], '_')->toString();

        return in_array($status, ['enrolled', 'not_enrolled', 'inactive'], true) ? $status : 'enrolled';
    }

    private function printedCor(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'printed'], true);
    }
}
