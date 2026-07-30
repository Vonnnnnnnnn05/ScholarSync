<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipPolicy;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Http\UploadedFile;

it('allows the registrar to maintain enrolled student records', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();

    $this->actingAs($registrar)
        ->get(route('registrar.enrolled-students.index'))
        ->assertOk()
        ->assertSee('Enrolled Student Records');

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.store'), [
            'student_id_number' => 'SKSU-2026-9001',
            'student_name' => 'Maria Santos',
            'course' => 'BS Information Technology',
            'year_level' => '4th Year',
            'campus' => 'ACCESS Campus',
            'enrollment_status' => 'enrolled',
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
        ])
        ->assertRedirect(route('registrar.enrolled-students.index'));

    expect(RegistrarStudent::where('student_id_number', 'SKSU-2026-9001')->exists())->toBeTrue();
});

it('allows the registrar to import enrolled student records from csv', function () {
    $registrar = User::factory()->role(UserRole::Registrar)->create();
    $csv = UploadedFile::fake()->createWithContent('registrar-enrollment.csv', implode("\n", [
        'student_id_number,student_name,course,year_level,campus,enrollment_status,academic_year,semester',
        'SKSU-2026-9101,Juan Dela Cruz,BS Information Technology,3rd Year,ACCESS Campus,enrolled,2026-2027,1st Semester',
        'SKSU-2026-9102,Ana Cruz,BS Agriculture,2nd Year,Lutayan Campus,enrolled,2026-2027,1st Semester',
    ]));

    $this->actingAs($registrar)
        ->post(route('registrar.enrolled-students.import'), [
            'enrollment_csv' => $csv,
        ])
        ->assertRedirect(route('registrar.enrolled-students.index'));

    expect(RegistrarStudent::where('student_id_number', 'SKSU-2026-9101')->exists())->toBeTrue()
        ->and(RegistrarStudent::where('student_id_number', 'SKSU-2026-9102')->exists())->toBeTrue()
        ->and(RegistrarStudent::where('student_id_number', 'SKSU-2026-9101')->value('course'))->toBe('BS Information Technology');
});

it('allows agencies to publish scholarship policies that students can view', function () {
    $agencyUser = User::factory()->role(UserRole::ScholarshipAgency)->create(['name' => 'CHED Agency']);
    $agency = Agency::factory()->for($agencyUser)->create(['agency_name' => 'CHED Agency']);
    $program = ScholarshipProgram::factory()->create([
        'name' => 'Merit Scholarship',
        'fund_source' => 'CHED',
        'agency_name' => $agency->agency_name,
    ]);

    $this->actingAs($agencyUser)
        ->post(route('agency.policies.store'), [
            'scholarship_program_id' => $program->id,
            'title' => 'Merit Scholarship Guidelines',
            'description' => 'Guidelines for qualified applicants.',
            'eligibility_requirements' => 'Must be officially enrolled.',
            'documentary_requirements' => 'Grades, ID, and certificate of registration.',
            'deadline' => '2026-07-31',
            'status' => 'published',
        ])
        ->assertRedirect(route('agency.policies.index'));

    expect(ScholarshipPolicy::where('title', 'Merit Scholarship Guidelines')->exists())->toBeTrue();

    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('student.scholarships.index'))
        ->assertOk()
        ->assertSee('Merit Scholarship Guidelines')
        ->assertSee('Must be officially enrolled')
        ->assertSee('CHED Agency');
});
