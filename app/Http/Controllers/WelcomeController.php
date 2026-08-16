<?php

namespace App\Http\Controllers;

use App\Enums\CertificateRequestStatus;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $summary = [
            'total_scholars' => Student::query()->count(),
            'pending_certificate_requests' => CertificateRequest::query()
                ->where('status', CertificateRequestStatus::Pending)
                ->count(),
            'verified_ors' => CertificateRequest::query()
                ->whereNotNull('verified_at')
                ->count(),
            'uploaded_masterlists' => ScholarshipMasterlist::query()->count(),
            'approved_records' => MasterlistRecord::query()
                ->where('chairman_status', 'approved')
                ->count(),
        ];

        $monitoringStats = [
            ['label' => 'Total scholars', 'value' => $summary['total_scholars']],
            ['label' => 'Pending certificate requests', 'value' => $summary['pending_certificate_requests']],
            ['label' => 'Verified official receipts', 'value' => $summary['verified_ors']],
            ['label' => 'Uploaded masterlists', 'value' => $summary['uploaded_masterlists']],
            ['label' => 'Approved scholar records', 'value' => $summary['approved_records']],
        ];

        return view('welcome', [
            'monitoringStats' => $monitoringStats,
            'monitoringMaxValue' => max(1, max(array_column($monitoringStats, 'value'))),
        ]);
    }
}
