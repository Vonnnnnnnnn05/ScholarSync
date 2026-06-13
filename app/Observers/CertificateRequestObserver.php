<?php

namespace App\Observers;

use App\Enums\CertificateRequestStatus;
use App\Models\CertificateRequest;
use App\Services\CertificateGenerationService;
use App\Services\CertificateRequestNotificationService;

class CertificateRequestObserver
{
    public function updated(CertificateRequest $certificateRequest): void
    {
        if (! $certificateRequest->wasChanged('status')) {
            return;
        }

        $notifications = app(CertificateRequestNotificationService::class);

        match ($certificateRequest->status) {
            CertificateRequestStatus::Approved => $this->handleApproved($certificateRequest, $notifications),
            CertificateRequestStatus::Rejected => $notifications->rejected($certificateRequest),
            default => null,
        };
    }

    private function handleApproved(
        CertificateRequest $certificateRequest,
        CertificateRequestNotificationService $notifications,
    ): void {
        app(CertificateGenerationService::class)->generateForRequest($certificateRequest);

        $notifications->approved($certificateRequest);
    }
}
