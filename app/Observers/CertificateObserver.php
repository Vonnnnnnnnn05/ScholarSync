<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Services\CertificateRequestNotificationService;

class CertificateObserver
{
    public function created(Certificate $certificate): void
    {
        app(CertificateRequestNotificationService::class)->certificateGenerated($certificate);
    }
}
