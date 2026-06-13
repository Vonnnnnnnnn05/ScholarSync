<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Mail\CertificateRequestStatusMail;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function certificateRequestForAdminReview(): CertificateRequest
{
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();

    return CertificateRequest::factory()
        ->for($student)
        ->create([
            'official_receipt' => 'certificate-requests/official-receipts/or.pdf',
            'purpose' => 'For employment scholarship clearance.',
        ]);
}

test('administrators can view submitted official receipt requests', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = certificateRequestForAdminReview();

    $this->actingAs($administrator)
        ->get(route('admin.official-receipts.index'))
        ->assertOk()
        ->assertSee('Official Receipt Verification')
        ->assertSee($certificateRequest->student->fullName())
        ->assertSee('Pending');

    $this->actingAs($administrator)
        ->get(route('admin.official-receipts.show', $certificateRequest))
        ->assertOk()
        ->assertSee('Download OR')
        ->assertSee('For employment scholarship clearance.');
});

test('administrators can download uploaded official receipt files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('certificate-requests/official-receipts/or.pdf', 'receipt');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = certificateRequestForAdminReview();

    $this->actingAs($administrator)
        ->get(route('admin.official-receipts.download', $certificateRequest))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('administrators can verify valid official receipts', function () {
    Mail::fake();

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = certificateRequestForAdminReview();

    $this->actingAs($administrator)
        ->patch(route('admin.official-receipts.verify', $certificateRequest))
        ->assertRedirect(route('admin.official-receipts.show', $certificateRequest));

    $certificateRequest->refresh();

    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Verified)
        ->and($certificateRequest->verified_by)->toBe($administrator->id)
        ->and($certificateRequest->verified_at)->not->toBeNull()
        ->and($certificateRequest->remarks)->toBeNull();

    Mail::assertNothingSent();
});

test('administrators must provide remarks when rejecting official receipts', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = certificateRequestForAdminReview();

    $this->actingAs($administrator)
        ->patch(route('admin.official-receipts.reject', $certificateRequest), ['remarks' => ''])
        ->assertSessionHasErrors('remarks');
});

test('administrators can reject invalid official receipts and notify students', function () {
    Mail::fake();

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $certificateRequest = certificateRequestForAdminReview();

    $this->actingAs($administrator)
        ->patch(route('admin.official-receipts.reject', $certificateRequest), [
            'remarks' => 'The uploaded Official Receipt is unreadable.',
        ])
        ->assertRedirect(route('admin.official-receipts.show', $certificateRequest));

    $certificateRequest->refresh();

    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Rejected)
        ->and($certificateRequest->verified_by)->toBe($administrator->id)
        ->and($certificateRequest->verified_at)->not->toBeNull()
        ->and($certificateRequest->remarks)->toBe('The uploaded Official Receipt is unreadable.');

    Mail::assertSent(CertificateRequestStatusMail::class, function (CertificateRequestStatusMail $mail) {
        return $mail->mailSubject === 'Certificate request rejected'
            && str_contains($mail->bodyMessage, 'unreadable');
    });

    expect(UserNotification::where('type', 'certificate_request_rejected')->exists())->toBeTrue();
});

test('non administrators cannot access official receipt verification', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('admin.official-receipts.index'))
        ->assertForbidden();
});
