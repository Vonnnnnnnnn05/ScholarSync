<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function certificateRequestPayload(array $overrides = []): array
{
    return array_merge([
        'student_id_number' => 'SKSU-2026-0001',
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'course' => 'BS Information Technology',
        'year_level' => '4th Year',
        'campus' => 'ACCESS Campus',
        'contact_number' => '09123456789',
        'purpose' => 'For submission to a private scholarship application office.',
        'official_receipt' => UploadedFile::fake()->create('official-receipt.pdf', 256, 'application/pdf'),
    ], $overrides);
}

test('students can view the certificate request form', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('student.certificate-requests.create'))
        ->assertOk()
        ->assertSee('Request Certificate of No Scholarship')
        ->assertSee('Official Receipt');
});

test('students can submit certificate requests with official receipt upload', function () {
    Storage::fake('local');

    $student = User::factory()->role(UserRole::Student)->create();

    $response = $this->actingAs($student)
        ->post(route('student.certificate-requests.store'), certificateRequestPayload());

    $certificateRequest = CertificateRequest::query()->firstOrFail();

    $response->assertRedirect(route('student.certificate-requests.show', $certificateRequest));

    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Pending)
        ->and($certificateRequest->purpose)->toBe('For submission to a private scholarship application office.')
        ->and($certificateRequest->student->user_id)->toBe($student->id);

    Storage::disk('local')->assertExists($certificateRequest->official_receipt);
});

test('official receipt upload validates file type and size', function () {
    Storage::fake('local');

    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->post(route('student.certificate-requests.store'), certificateRequestPayload([
            'official_receipt' => UploadedFile::fake()->create('receipt.exe', 100, 'application/octet-stream'),
        ]))
        ->assertSessionHasErrors('official_receipt');

    $this->actingAs($student)
        ->post(route('student.certificate-requests.store'), certificateRequestPayload([
            'official_receipt' => UploadedFile::fake()->create('receipt.pdf', 6000, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('official_receipt');
});

test('students can view request history and progress details', function () {
    $user = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Verified)
        ->create(['remarks' => 'Official receipt verified.']);

    $this->actingAs($user)
        ->get(route('student.certificate-requests.index'))
        ->assertOk()
        ->assertSee('Certificate Request History')
        ->assertSee('Verified')
        ->assertSee('Official receipt verified.');

    $this->actingAs($user)
        ->get(route('student.certificate-requests.show', $certificateRequest))
        ->assertOk()
        ->assertSee('Current Status')
        ->assertSee('Verified')
        ->assertSee('Official receipt verified.');
});

test('students cannot view another students certificate request', function () {
    $owner = User::factory()->role(UserRole::Student)->create();
    $other = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($owner)->create();
    $certificateRequest = CertificateRequest::factory()->for($student)->create();

    $this->actingAs($other)
        ->get(route('student.certificate-requests.show', $certificateRequest))
        ->assertNotFound();
});

test('non student users cannot access student certificate request module', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('student.certificate-requests.index'))
        ->assertForbidden();
});

test('approved requests can download temporary sample docx certificate', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($user)->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Approved)
        ->create();

    $this->actingAs($user)
        ->get(route('student.certificate-requests.certificate.download', $certificateRequest))
        ->assertOk()
        ->assertHeader('content-disposition');

    Storage::disk('local')->assertExists('certificates/samples/certificate-of-no-scholarship-sample.docx');
});
