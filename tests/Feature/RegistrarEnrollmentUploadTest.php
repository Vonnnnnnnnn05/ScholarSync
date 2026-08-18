<?php

use App\Enums\UserRole;
use App\Models\RegistrarStudent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function enrollmentWorkbook(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'enrollment-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['student_id_number', 'student_name', 'course', 'cor_printed'],
        ['SKSU-2026-0001', 'Ana Cruz', 'BSIT', 'yes'],
    ]);
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'enrollment-records.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function directStringEnrollmentWorkbook(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'direct-string-enrollment-');
    (new Xlsx(new Spreadsheet))->save($path);

    $archive = new ZipArchive;
    $archive->open($path);
    $archive->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
            <x:sheetData>
                <x:row r="1"><x:c r="A1" t="str"><x:v>student_id_number</x:v></x:c><x:c r="B1" t="str"><x:v>student_name</x:v></x:c><x:c r="C1" t="str"><x:v>cor_printed</x:v></x:c></x:row>
                <x:row r="2"><x:c r="A2" t="str"><x:v>SKSU-2026-A001</x:v></x:c><x:c r="B2" t="str"><x:v>Carlo James Reyes</x:v></x:c><x:c r="C2" t="str"><x:v>printed</x:v></x:c></x:row>
            </x:sheetData>
        </x:worksheet>
        XML);
    $archive->close();

    return new UploadedFile($path, 'direct-string-enrollment.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('registrar can upload Excel enrollment records and cannot manually add one', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index'))
        ->assertOk()
        ->assertSee('Upload Enrollment Records')
        ->assertDontSee('Add Enrolled Student');

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.import'), ['enrollment_file' => enrollmentWorkbook()])
        ->assertRedirect(route('registrar.enrolled-students.index'))
        ->assertSessionHasNoErrors();

    expect(RegistrarStudent::query()->where('student_id_number', 'SKSU-2026-0001')->first())
        ->not->toBeNull()
        ->course->toBe('BSIT')
        ->cor_printed->toBeTrue();

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index'))
        ->assertSee('Printed');
});

test('the manual enrolled-student route is unavailable', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->post('/registrar/enrolled-students', ['student_id_number' => 'SKSU-2026-0001'])
        ->assertStatus(405);
});

test('registrar can import prefixed direct string Excel workbooks', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.import'), ['enrollment_file' => directStringEnrollmentWorkbook()])
        ->assertRedirect(route('registrar.enrolled-students.index'))
        ->assertSessionHasNoErrors();

    expect(RegistrarStudent::query()->where('student_id_number', 'SKSU-2026-A001')->first())
        ->not->toBeNull()
        ->student_name->toBe('Carlo James Reyes')
        ->cor_printed->toBeTrue();
});
