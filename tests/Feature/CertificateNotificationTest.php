<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Mail\CertificateRequestStatusMail;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('approved certificate requests send student notifications', function () {
    Mail::fake();
    Storage::fake('local');

    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $certificateRequest = CertificateRequest::factory()->for($student)->create();

    $certificateRequest->update([
        'status' => CertificateRequestStatus::Approved,
        'approved_by' => User::factory()->role(UserRole::ScholarshipChairman)->create()->id,
        'approved_at' => now(),
    ]);

    Mail::assertSent(CertificateRequestStatusMail::class, function (CertificateRequestStatusMail $mail) use ($certificateRequest) {
        return $mail->certificateRequest->is($certificateRequest)
            && $mail->mailSubject === 'Certificate request approved';
    });

    $certificateRequest->refresh();

    expect(UserNotification::where('user_id', $studentUser->id)
        ->where('type', 'certificate_request_approved')
        ->exists())->toBeTrue()
        ->and($certificateRequest->certificate)->not->toBeNull()
        ->and($certificateRequest->certificate->certificate_number)->toStartWith('CERT-')
        ->and(Storage::disk('local')->exists($certificateRequest->certificate->file_path))->toBeTrue();
});

test('generated certificates send student notifications', function () {
    Mail::fake();

    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Approved)
        ->create();

    $certificate = Certificate::create([
        'certificate_request_id' => $certificateRequest->id,
        'certificate_number' => 'CERT-2026-0001',
        'file_path' => 'certificates/generated/cert-2026-0001.docx',
        'generated_by' => User::factory()->role(UserRole::Administrator)->create()->id,
        'generated_at' => now(),
    ]);

    Mail::assertSent(CertificateRequestStatusMail::class, function (CertificateRequestStatusMail $mail) use ($certificateRequest) {
        return $mail->certificateRequest->is($certificateRequest)
            && $mail->mailSubject === 'Certificate generated';
    });

    expect($certificate->exists)->toBeTrue()
        ->and(UserNotification::where('user_id', $studentUser->id)
            ->where('type', 'certificate_generated')
            ->exists())->toBeTrue();
});
