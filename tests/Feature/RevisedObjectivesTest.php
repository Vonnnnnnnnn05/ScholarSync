<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipPolicy;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function revisedEnrollmentWorkbook(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'revised-enrollment-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        ['student_id_number', 'student_name', 'course'],
        ['SKSU-2026-9101', 'Juan Dela Cruz', 'BS Information Technology'],
        ['SKSU-2026-9102', 'Ana Cruz', 'BS Agriculture'],
    ]);
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'registrar-enrollment.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('shows the registrar an upload-only enrollment workflow', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index'))
        ->assertOk()
        ->assertSee('Upload Enrollment Records')
        ->assertDontSee('Add Enrolled Student');
});

it('allows the registrar to import enrolled student records from Excel', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.import'), [
            'enrollment_file' => revisedEnrollmentWorkbook(),
        ])
        ->assertRedirect(route('registrar.enrolled-students.index'));

    expect(RegistrarStudent::where('student_id_number', 'SKSU-2026-9101')->exists())->toBeTrue()
        ->and(RegistrarStudent::where('student_id_number', 'SKSU-2026-9102')->exists())->toBeTrue()
        ->and(RegistrarStudent::where('student_id_number', 'SKSU-2026-9101')->value('course'))->toBe('BS Information Technology');
});

it('allows administrators to publish scholarship opportunities that students can view', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $agency = Agency::factory()->create(['agency_name' => 'CHED Agency']);
    $program = ScholarshipProgram::factory()->create([
        'name' => 'Merit Scholarship',
        'fund_source' => 'CHED',
        'agency_name' => $agency->agency_name,
    ]);

    $this->actingAs($administrator)
        ->post(route('admin.scholarships.store'), [
            'agency' => 'CHED Agency',
            'program' => 'Merit Scholarship',
            'title' => 'Merit Scholarship Guidelines',
            'description' => 'Guidelines for qualified applicants.',
            'eligibility_requirements' => 'Must be officially enrolled.',
            'documentary_requirements' => 'Grades, ID, and certificate of registration.',
            'deadline' => '2026-07-31',
            'application_link' => 'https://ched.gov.ph/apply',
            'status' => 'published',
        ])
        ->assertRedirect(route('admin.scholarships.index'));

    $policy = ScholarshipPolicy::query()->where('title', 'Merit Scholarship Guidelines')->firstOrFail();

    expect($policy->agency->agency_name)->toBe('CHED Agency')
        ->and($policy->program?->is($program))->toBeTrue()
        ->and(Agency::where('agency_name', 'CHED Agency')->count())->toBe(1)
        ->and(ScholarshipProgram::where('name', 'Merit Scholarship')->count())->toBe(1);

    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('student.scholarships.index'))
        ->assertOk()
        ->assertSee('Merit Scholarship Guidelines')
        ->assertSee('Must be officially enrolled')
        ->assertSee('CHED Agency')
        ->assertSee('https://ched.gov.ph/apply');
});

it('shows fillable agency and program fields on scholarship opportunities', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('admin.scholarships.index'))
        ->assertOk()
        ->assertSee('name="agency"', false)
        ->assertSee('name="program"', false)
        ->assertDontSee('name="agency_id"', false)
        ->assertDontSee('name="scholarship_program_id"', false);
});

it('allows administrators to update and delete scholarship opportunities without deleting their agency or program', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $agency = Agency::factory()->create(['agency_name' => 'CHED']);
    $program = ScholarshipProgram::factory()->create([
        'name' => 'Merit Program',
        'fund_source' => 'CHED',
        'agency_name' => 'CHED',
    ]);
    $policy = ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'scholarship_program_id' => $program->id,
        'title' => 'Old Opportunity',
        'application_link' => 'https://example.test/old',
        'status' => 'draft',
    ]);

    $this->actingAs($administrator)
        ->patch(route('admin.scholarships.update', $policy), [
            'agency' => 'CHED',
            'program' => 'Merit Program',
            'title' => 'Updated Opportunity',
            'description' => 'Updated description.',
            'eligibility_requirements' => 'Currently enrolled.',
            'documentary_requirements' => 'Registration form.',
            'deadline' => '2026-09-30',
            'application_link' => 'https://example.test/apply',
            'status' => 'published',
        ])
        ->assertRedirect(route('admin.scholarships.index'));

    expect($policy->fresh()->title)->toBe('Updated Opportunity');

    $this->actingAs($administrator)
        ->delete(route('admin.scholarships.destroy', $policy))
        ->assertRedirect(route('admin.scholarships.index'));

    expect(ScholarshipPolicy::find($policy->id))->toBeNull()
        ->and(Agency::find($agency->id))->not->toBeNull()
        ->and(ScholarshipProgram::find($program->id))->not->toBeNull();
});

it('shows only opportunity management with view edit and delete actions', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $agency = Agency::factory()->create(['agency_name' => 'CHED']);
    $program = ScholarshipProgram::factory()->create(['name' => 'Merit Program', 'agency_name' => 'CHED']);
    ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'scholarship_program_id' => $program->id,
        'title' => 'Merit Opportunity',
        'application_link' => 'https://example.test/apply',
        'status' => 'published',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.scholarships.index'))
        ->assertOk()
        ->assertSee('Scholarship Opportunity Management')
        ->assertSee('aria-label="View opportunity"', false)
        ->assertSee('aria-label="Edit opportunity"', false)
        ->assertSee('aria-label="Delete opportunity"', false)
        ->assertSee('x-show="viewOpen"', false)
        ->assertSee('x-show="editOpen"', false)
        ->assertSee('x-show="deleteOpen"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertDontSee('onsubmit="return confirm(', false)
        ->assertDontSee('Agency Management')
        ->assertDontSee('Program Management');
});

it('allows administrators to deactivate and reactivate opportunities while students see published records only', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $student = User::factory()->role(UserRole::Student)->create();
    $agency = Agency::factory()->create(['agency_name' => 'CHED']);
    $program = ScholarshipProgram::factory()->create(['name' => 'Merit Program', 'agency_name' => 'CHED']);
    $policy = ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'scholarship_program_id' => $program->id,
        'title' => 'Lifecycle Scholarship',
        'application_link' => 'https://example.test/apply',
        'status' => 'published',
    ]);
    $payload = [
        'agency' => 'CHED',
        'program' => 'Merit Program',
        'title' => 'Lifecycle Scholarship',
        'description' => null,
        'eligibility_requirements' => null,
        'documentary_requirements' => null,
        'deadline' => null,
        'application_link' => 'https://example.test/apply',
    ];

    $this->actingAs($administrator)
        ->patch(route('admin.scholarships.update', $policy), array_merge($payload, ['status' => 'inactive']))
        ->assertRedirect(route('admin.scholarships.index'));

    expect($policy->fresh()->status)->toBe('inactive');
    $this->actingAs($student)->get(route('student.scholarships.index'))->assertDontSee('Lifecycle Scholarship');

    $this->actingAs($administrator)
        ->patch(route('admin.scholarships.update', $policy), array_merge($payload, ['status' => 'published']))
        ->assertRedirect(route('admin.scholarships.index'));

    expect($policy->fresh()->status)->toBe('published');
    $this->actingAs($student)->get(route('student.scholarships.index'))->assertSee('Lifecycle Scholarship');
    $this->actingAs($administrator)->get(route('admin.scholarships.index'))->assertSee('Inactive');
});
