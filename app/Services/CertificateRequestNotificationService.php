<?php

namespace App\Services;

use App\Mail\CertificateRequestStatusMail;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CertificateRequestNotificationService
{
    public function rejected(CertificateRequest $certificateRequest): void
    {
        $message = 'Your Certificate of No Scholarship request was rejected. Reason: '.$certificateRequest->remarks;

        $this->send(
            certificateRequest: $certificateRequest,
            subject: 'Certificate request rejected',
            title: 'Certificate request rejected',
            message: $message,
            type: 'certificate_request_rejected',
        );
    }

    public function approved(CertificateRequest $certificateRequest): void
    {
        $this->send(
            certificateRequest: $certificateRequest,
            subject: 'Certificate request approved',
            title: 'Certificate request approved',
            message: 'Your Certificate of No Scholarship request has been approved. You may now download the certificate when it is available.',
            type: 'certificate_request_approved',
            actionText: 'View request',
        );
    }

    public function certificateGenerated(Certificate $certificate): void
    {
        $certificateRequest = $certificate->certificateRequest()->with('student.user')->firstOrFail();

        $this->send(
            certificateRequest: $certificateRequest,
            subject: 'Certificate generated',
            title: 'Certificate generated',
            message: 'Your Certificate of No Scholarship has been generated and is ready for download.',
            type: 'certificate_generated',
            actionText: 'Download certificate',
            actionUrl: route('student.certificate-requests.certificate.download', $certificateRequest),
        );
    }

    private function send(
        CertificateRequest $certificateRequest,
        string $subject,
        string $title,
        string $message,
        string $type,
        ?string $actionText = null,
        ?string $actionUrl = null,
    ): void {
        $certificateRequest->loadMissing('student.user');
        $studentUser = $certificateRequest->student->user;
        $actionUrl ??= route('student.certificate-requests.show', $certificateRequest);

        UserNotification::create([
            'user_id' => $studentUser->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);

        try {
            Mail::to($studentUser->email)->send(new CertificateRequestStatusMail(
                certificateRequest: $certificateRequest,
                mailSubject: $subject,
                title: $title,
                bodyMessage: $message,
                actionUrl: $actionUrl,
                actionText: $actionText ?? 'View request',
            ));
        } catch (Throwable $exception) {
            Log::warning('Certificate request email notification could not be sent.', [
                'certificate_request_id' => $certificateRequest->id,
                'recipient' => $studentUser->email,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
