<?php

use App\Enums\UserRole;
use App\Models\RegistrarStudent;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function enrollmentWorkbook(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'enrollment-');
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::OVERWRITE);
    $archive->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>student_id_number</t></si><si><t>student_name</t></si><si><t>course</t></si><si><t>cor_printed</t></si><si><t>SKSU-2026-0001</t></si><si><t>Ana Cruz</t></si><si><t>BSIT</t></si><si><t>yes</t></si></sst>');
    $archive->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row><row r="2"><c r="A2" t="s"><v>4</v></c><c r="B2" t="s"><v>5</v></c><c r="C2" t="s"><v>6</v></c><c r="D2" t="s"><v>7</v></c></row></sheetData></worksheet>');
    $archive->close();

    return new UploadedFile($path, 'enrollment-records.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
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
