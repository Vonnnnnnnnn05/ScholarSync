<?php

use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use App\Mail\ScholarshipEvaluationResultMail;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRequirement;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function scholarshipRenewalPayload(array $overrides = []): array
{
    return array_merge([
        'scholarship_program' => 'Continuing Merit Scholarship',
        'fund_source' => 'CHED',
        'grades_file' => UploadedFile::fake()->create('grades.pdf', 200, 'application/pdf'),
        'enrollment_file' => UploadedFile::fake()->create('enrollment.pdf', 200, 'application/pdf'),
        'valid_id_file' => UploadedFile::fake()->create('school-id.pdf', 200, 'application/pdf'),
    ], $overrides);
}

test('students can submit scholarship renewal requirements', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->post(route('student.scholarship-renewals.store'), scholarshipRenewalPayload());

    $application = ScholarshipApplication::query()->firstOrFail();

    $response->assertRedirect(route('student.scholarship-renewals.show', $application));

    expect($application->student_id)->toBe($student->id)
        ->and($application->status)->toBe(ScholarshipApplicationStatus::Submitted)
        ->and($application->requirements)->toHaveCount(3);

    foreach ($application->requirements as $requirement) {
        Storage::disk('local')->assertExists($requirement->file_path);
    }
});

test('renewal upload validates required documents', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($user)
        ->post(route('student.scholarship-renewals.store'), scholarshipRenewalPayload([
            'grades_file' => null,
        ]))
        ->assertSessionHasErrors('grades_file');
});

test('students can track scholarship renewal status', function () {
    $user = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();
    $application = ScholarshipApplication::factory()
        ->for($student)
        ->create([
            'status' => ScholarshipApplicationStatus::UnderEvaluation,
            'remarks' => 'Initial review started.',
        ]);

    $this->actingAs($user)
        ->get(route('student.scholarship-renewals.index'))
        ->assertOk()
        ->assertSee('Under Evaluation')
        ->assertSee('Initial review started.');

    $this->actingAs($user)
        ->get(route('student.scholarship-renewals.show', $application))
        ->assertOk()
        ->assertSee('Under Evaluation');
});

test('administrators and coordinators can evaluate submitted renewal requirements', function () {
    Mail::fake();

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $application = ScholarshipApplication::factory()
        ->for($student)
        ->create(['status' => ScholarshipApplicationStatus::Submitted]);

    $this->actingAs($administrator)
        ->get(route('evaluator.scholarship-renewals.index'))
        ->assertOk()
        ->assertSee('Renewal Applications');

    $this->actingAs($administrator)
        ->patch(route('evaluator.scholarship-renewals.update', $application), [
            'status' => ScholarshipApplicationStatus::Approved->value,
            'remarks' => 'Renewal approved.',
        ])
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ScholarshipApplicationStatus::Approved)
        ->and($application->remarks)->toBe('Renewal approved.')
        ->and($application->evaluated_by)->toBe($administrator->id)
        ->and($application->evaluated_at)->not->toBeNull();

    Mail::assertSent(ScholarshipEvaluationResultMail::class);
    expect(UserNotification::where('user_id', $studentUser->id)->exists())->toBeTrue();
});

test('rejection and revision requests require remarks', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $application = ScholarshipApplication::factory()->create();

    $this->actingAs($coordinator)
        ->patch(route('evaluator.scholarship-renewals.update', $application), [
            'status' => ScholarshipApplicationStatus::Rejected->value,
            'remarks' => '',
        ])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($coordinator)
        ->patch(route('evaluator.scholarship-renewals.update', $application), [
            'status' => ScholarshipApplicationStatus::NeedRevision->value,
            'remarks' => 'Upload a clearer copy of grades.',
        ])
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ScholarshipApplicationStatus::NeedRevision);
});

test('students can resubmit requirements marked need revision', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();
    $application = ScholarshipApplication::factory()
        ->for($student)
        ->create([
            'status' => ScholarshipApplicationStatus::NeedRevision,
            'remarks' => 'Upload clearer files.',
        ]);
    ScholarshipRequirement::factory()->for($application, 'application')->create([
        'file_path' => 'scholarship-renewals/old-grades.pdf',
    ]);
    Storage::disk('local')->put('scholarship-renewals/old-grades.pdf', 'old');

    $this->actingAs($user)
        ->patch(route('student.scholarship-renewals.revise', $application), scholarshipRenewalPayload([
            'scholarship_program' => 'Updated Continuing Scholarship',
        ]))
        ->assertRedirect(route('student.scholarship-renewals.show', $application));

    expect($application->refresh()->status)->toBe(ScholarshipApplicationStatus::Submitted)
        ->and($application->scholarship_program)->toBe('Updated Continuing Scholarship')
        ->and($application->remarks)->toBeNull()
        ->and($application->requirements()->count())->toBe(3);

    Storage::disk('local')->assertMissing('scholarship-renewals/old-grades.pdf');
});

test('students cannot view another students renewal application', function () {
    $owner = User::factory()->role(UserRole::Student)->create();
    $other = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($owner)->create();
    $application = ScholarshipApplication::factory()->for($student)->create();

    $this->actingAs($other)
        ->get(route('student.scholarship-renewals.show', $application))
        ->assertNotFound();
});

test('unauthorized roles cannot access evaluator renewal workflow', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('evaluator.scholarship-renewals.index'))
        ->assertForbidden();
});
