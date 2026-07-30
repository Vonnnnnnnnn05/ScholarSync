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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrolledStudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        return view('registrar.enrolled-students.index', [
            'search' => $search,
            'students' => RegistrarStudent::query()
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

    public function store(Request $request, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:255', 'unique:registrar_students,student_id_number'],
            'student_name' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'enrollment_status' => ['required', 'string', Rule::in(['enrolled', 'not_enrolled', 'inactive'])],
            'academic_year' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'string', 'max:255'],
        ]);

        $student = RegistrarStudent::create($validated);

        $audit->record('registrar_student_created', $student, $validated, $request);

        return redirect()->route('registrar.enrolled-students.index')->with('status', 'Registrar enrolled-student record added.');
    }

    public function update(Request $request, RegistrarStudent $enrolledStudent, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:255', Rule::unique('registrar_students', 'student_id_number')->ignore($enrolledStudent)],
            'student_name' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'enrollment_status' => ['required', 'string', Rule::in(['enrolled', 'not_enrolled', 'inactive'])],
            'academic_year' => ['nullable', 'string', 'max:255'],
            'semester' => ['nullable', 'string', 'max:255'],
        ]);

        $enrolledStudent->update($validated);

        $audit->record('registrar_student_updated', $enrolledStudent, $validated, $request);

        return redirect()->route('registrar.enrolled-students.index')->with('status', 'Registrar enrolled-student record updated.');
    }

    public function import(Request $request, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->csvRows($validated['enrollment_csv']);
        $requiredColumns = ['student_id_number', 'student_name'];
        $missingColumns = array_values(array_diff($requiredColumns, $rows['headers']));

        if ($missingColumns !== []) {
            return back()
                ->withErrors(['enrollment_csv' => 'CSV is missing required columns: '.implode(', ', $missingColumns).'.'])
                ->withInput();
        }

        $imported = DB::transaction(function () use ($rows): int {
            $count = 0;

            foreach ($rows['rows'] as $row) {
                $studentIdNumber = trim((string) ($row['student_id_number'] ?? ''));
                $studentName = trim((string) ($row['student_name'] ?? ''));

                if ($studentIdNumber === '' || $studentName === '') {
                    continue;
                }

                RegistrarStudent::updateOrCreate(
                    ['student_id_number' => $studentIdNumber],
                    [
                        'student_name' => $studentName,
                        'course' => $this->nullableValue($row['course'] ?? null),
                        'year_level' => $this->nullableValue($row['year_level'] ?? null),
                        'campus' => $this->nullableValue($row['campus'] ?? null),
                        'enrollment_status' => $this->validEnrollmentStatus($row['enrollment_status'] ?? null),
                        'academic_year' => $this->nullableValue($row['academic_year'] ?? null),
                        'semester' => $this->nullableValue($row['semester'] ?? null),
                    ],
                );

                $count++;
            }

            return $count;
        });

        $audit->record('registrar_students_imported', new RegistrarStudent, [
            'imported_count' => $imported,
        ], $request);

        return redirect()
            ->route('registrar.enrolled-students.index')
            ->with('status', "{$imported} registrar enrollment record(s) imported.");
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string|null>>}
     */
    private function csvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false) {
            fclose($handle);

            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rawHeaders);
        $allowedColumns = [
            'student_id_number',
            'student_name',
            'course',
            'year_level',
            'campus',
            'enrollment_status',
            'academic_year',
            'semester',
        ];
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if (collect($values)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = Arr::only($row, $allowedColumns);
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
