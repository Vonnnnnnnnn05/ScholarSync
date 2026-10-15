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

test('registrar can download sample enrollment excel template', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('registrar can import official sksu format with split names and enrollment status without cor column', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $path = tempnam(sys_get_temp_dir(), 'sksu-format-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['Campus', 'No', 'Student ID', 'LRN', 'Last Name', 'First Name', 'Middle Initial', 'Course', 'Year Level', 'Sex', 'Email', 'Contact Number', 'Enrollment Status'],
        ['ACCESS', 1, '25197', '130664110001', 'ABAN', 'JESSALYN', 'P', 'Bachelor of Elementary Education', 2, 'F', 'jessalyn@sksu.edu.ph', '09700410752', 'Enrolled'],
        ['ACCESS', 2, '23261', '130664110002', 'YBANES', 'CARL HARRY', 'M', 'BSED-Social Studies', 3, 'M', 'carl@sksu.edu.ph', '09123456789', 'No COR Printed'],
        ['ACCESS', 3, '24001', '130664110003', 'DELA CRUZ', 'JUAN', 'A', 'BSIT', 1, 'M', 'juan@sksu.edu.ph', '09987654321', 'Unenrolled'],
    ]);
    (new Xlsx($spreadsheet))->save($path);

    $file = new UploadedFile($path, 'sksu-enrollment.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.import'), ['enrollment_file' => $file])
        ->assertRedirect(route('registrar.enrolled-students.index'))
        ->assertSessionHasNoErrors();

    $student1 = RegistrarStudent::query()->where('student_id_number', '25197')->first();
    expect($student1)->not->toBeNull();
    expect($student1->student_name)->toBe('ABAN, JESSALYN P.');
    expect($student1->course)->toBe('Bachelor of Elementary Education');
    expect($student1->enrollment_status)->toBe('enrolled');
    expect($student1->cor_printed)->toBeTrue();

    $student2 = RegistrarStudent::query()->where('student_id_number', '23261')->first();
    expect($student2)->not->toBeNull();
    expect($student2->student_name)->toBe('YBANES, CARL HARRY M.');
    expect($student2->enrollment_status)->toBe('enrolled');
    expect($student2->cor_printed)->toBeFalse();

    $student3 = RegistrarStudent::query()->where('student_id_number', '24001')->first();
    expect($student3)->not->toBeNull();
    expect($student3->enrollment_status)->toBe('not_enrolled');
    expect($student3->cor_printed)->toBeFalse();

    // Verify view has Enrollment Status and no separate COR Status column
    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index'))
        ->assertOk()
        ->assertSee('Enrollment Status')
        ->assertDontSee('COR Status');

    // Verify search bar can search by status
    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index', ['search' => 'no cor printed']))
        ->assertOk()
        ->assertSee('YBANES, CARL HARRY M.')
        ->assertDontSee('ABAN, JESSALYN P.');

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index', ['search' => 'unenrolled']))
        ->assertOk()
        ->assertSee('DELA CRUZ, JUAN A.')
        ->assertDontSee('ABAN, JESSALYN P.');
});

test('registrar can delete an individual student record and cannot delete another campus record', function () {
    $campusA = \App\Models\Campus::factory()->create(['name' => 'Campus Alpha']);
    $campusB = \App\Models\Campus::factory()->create(['name' => 'Campus Beta']);

    $registrarA = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $campusA->id]);

    $studentA = RegistrarStudent::create([
        'student_id_number' => 'ALPHA-001',
        'student_name' => 'Alpha Student',
        'campus_id' => $campusA->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    $studentB = RegistrarStudent::create([
        'student_id_number' => 'BETA-001',
        'student_name' => 'Beta Student',
        'campus_id' => $campusB->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    // Deleting own campus student succeeds
    $this->actingAs($registrarA)
        ->delete(route('registrar.enrolled-students.destroy', $studentA))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(RegistrarStudent::find($studentA->id))->toBeNull();

    // Deleting another campus student fails with 403
    $this->actingAs($registrarA)
        ->delete(route('registrar.enrolled-students.destroy', $studentB))
        ->assertForbidden();

    expect(RegistrarStudent::find($studentB->id))->not->toBeNull();
});

test('registrar can clear all enrollment records for their campus without affecting other campuses', function () {
    $campusA = \App\Models\Campus::factory()->create(['name' => 'Campus Alpha']);
    $campusB = \App\Models\Campus::factory()->create(['name' => 'Campus Beta']);

    $registrarA = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $campusA->id]);

    RegistrarStudent::create([
        'student_id_number' => 'ALPHA-001',
        'student_name' => 'Alpha 1',
        'campus_id' => $campusA->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);
    RegistrarStudent::create([
        'student_id_number' => 'ALPHA-002',
        'student_name' => 'Alpha 2',
        'campus_id' => $campusA->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    RegistrarStudent::create([
        'student_id_number' => 'BETA-001',
        'student_name' => 'Beta 1',
        'campus_id' => $campusB->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    expect(RegistrarStudent::where('campus_id', $campusA->id)->count())->toBe(2);
    expect(RegistrarStudent::where('campus_id', $campusB->id)->count())->toBe(1);

    $this->actingAs($registrarA)
        ->delete(route('registrar.enrolled-students.clear'))
        ->assertRedirect(route('registrar.enrolled-students.index'))
        ->assertSessionHas('status');

    // Campus A records are cleared, Campus B records remain intact
    expect(RegistrarStudent::where('campus_id', $campusA->id)->count())->toBe(0);
    expect(RegistrarStudent::where('campus_id', $campusB->id)->count())->toBe(1);
});



