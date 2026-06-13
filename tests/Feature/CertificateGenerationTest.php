<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Mail\CertificateRequestStatusMail;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function verifiedCertificateRequest(): CertificateRequest
{
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();

    return CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Verified)
        ->create();
}

test('administrator approval generates a numbered pdf certificate', function () {
    Mail::fake();
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = verifiedCertificateRequest();

    $this->actingAs($administrator)
        ->patch(route('admin.official-receipts.approve', $certificateRequest))
        ->assertRedirect(route('admin.official-receipts.show', $certificateRequest));

    $certificateRequest->refresh();
    $certificate = $certificateRequest->certificate;

    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Approved)
        ->and($certificateRequest->approved_by)->toBe($administrator->id)
        ->and($certificateRequest->approved_at)->not->toBeNull()
        ->and($certificate)->not->toBeNull()
        ->and($certificate->certificate_number)->toMatch('/^CERT-\d{4}-\d{6}$/')
        ->and($certificate->file_path)->toEndWith('.pdf');

    Storage::disk('local')->assertExists($certificate->file_path);
    Mail::assertSent(CertificateRequestStatusMail::class);
});

test('certificate numbers are unique and sequential per year', function () {
    Mail::fake();
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $firstRequest = verifiedCertificateRequest();
    $secondRequest = verifiedCertificateRequest();

    $this->actingAs($administrator)->patch(route('admin.official-receipts.approve', $firstRequest));
    $this->actingAs($administrator)->patch(route('admin.official-receipts.approve', $secondRequest));

    $firstNumber = $firstRequest->fresh()->certificate->certificate_number;
    $secondNumber = $secondRequest->fresh()->certificate->certificate_number;

    expect($firstNumber)->not->toBe($secondNumber)
        ->and($secondNumber)->toEndWith('000002');
});

test('student can download own generated certificate pdf only', function () {
    Mail::fake();
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = verifiedCertificateRequest();
    $studentUser = $certificateRequest->student->user;
    $otherStudent = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($administrator)->patch(route('admin.official-receipts.approve', $certificateRequest));

    $this->actingAs($studentUser)
        ->get(route('student.certificate-requests.certificate.download', $certificateRequest))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($otherStudent)
        ->get(route('student.certificate-requests.certificate.download', $certificateRequest))
        ->assertNotFound();
});

test('administrator can view certificate history and download records', function () {
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Approved)
        ->create();
    $certificate = Certificate::create([
        'certificate_request_id' => $certificateRequest->id,
        'certificate_number' => 'CERT-2026-000123',
        'file_path' => 'certificates/generated/cert-2026-000123.pdf',
        'generated_by' => $administrator->id,
        'generated_at' => now(),
    ]);
    Storage::disk('local')->put($certificate->file_path, '%PDF-1.4');

    $this->actingAs($administrator)
        ->get(route('admin.certificates.index'))
        ->assertOk()
        ->assertSee('Generated Certificate History')
        ->assertSee('CERT-2026-000123')
        ->assertSee($certificateRequest->student->fullName());

    $this->actingAs($administrator)
        ->get(route('admin.certificates.download', $certificate))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('non administrators cannot view certificate history', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('admin.certificates.index'))
        ->assertForbidden();
});
