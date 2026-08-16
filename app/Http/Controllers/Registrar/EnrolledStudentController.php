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
use RuntimeException;
use ZipArchive;

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
        $archive = new ZipArchive;

        if ($archive->open($file->getRealPath()) !== true) {
            throw new RuntimeException('The uploaded file could not be read as an Excel workbook.');
        }

        try {
            $sharedStrings = $this->sharedStrings($archive);
            $sheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new RuntimeException('The Excel workbook does not contain a readable first worksheet.');
            }

            $sheet = simplexml_load_string($sheetXml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

            if ($sheet === false) {
                throw new RuntimeException('The first worksheet could not be read.');
            }

            $sheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $xmlRows = $sheet->xpath('//main:sheetData/main:row') ?: [];
            $values = array_map(fn (\SimpleXMLElement $row) => $this->worksheetValues($row, $sharedStrings), $xmlRows);

            if ($values === []) {
                return ['headers' => [], 'rows' => []];
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($values));
            $allowedColumns = ['student_id_number', 'student_name', 'course', 'year_level', 'campus', 'enrollment_status', 'academic_year', 'semester'];
            $rows = [];

            foreach ($values as $valueRow) {
                if (collect($valueRow)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                    continue;
                }

                $row = [];
                foreach ($headers as $index => $header) {
                    $row[$header] = $valueRow[$index] ?? null;
                }
                $rows[] = Arr::only($row, $allowedColumns);
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            $archive->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $archive): array
    {
        $xml = $archive->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if ($strings === false) {
            throw new RuntimeException('The workbook shared strings could not be read.');
        }

        $strings->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(function (\SimpleXMLElement $string): string {
            $string->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            return implode('', array_map(fn (\SimpleXMLElement $text) => (string) $text, $string->xpath('.//main:t') ?: []));
        }, $strings->xpath('//main:si') ?: []);
    }

    /** @param array<int, string> $sharedStrings @return array<int, string> */
    private function worksheetValues(\SimpleXMLElement $row, array $sharedStrings): array
    {
        $row->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = [];

        foreach ($row->xpath('./main:c') ?: [] as $cell) {
            $reference = (string) $cell['r'];
            preg_match('/([A-Z]+)/', $reference, $matches);
            $column = $this->columnIndex($matches[1] ?? 'A');
            $type = (string) $cell['t'];
            $value = (string) ($cell->v ?? '');

            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) ($cell->is->t ?? '');
            }

            $values[$column] = $value;
        }

        return $values === [] ? [] : array_values($values + array_fill(0, max(array_keys($values)) + 1, ''));
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        foreach (str_split($column) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
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
}
