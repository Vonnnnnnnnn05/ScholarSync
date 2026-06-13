<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;
use Illuminate\View\View;

class MonitoringDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.monitoring.dashboard', [
            'summary' => [
                'total_scholars' => Student::query()->count(),
                'pending_certificate_requests' => CertificateRequest::query()
                    ->where('status', CertificateRequestStatus::Pending)
                    ->count(),
                'verified_ors' => CertificateRequest::query()
                    ->whereNotNull('verified_at')
                    ->count(),
                'uploaded_masterlists' => ScholarshipMasterlist::query()->count(),
                'pending_evaluations' => ScholarshipApplication::query()
                    ->whereIn('status', [
                        ScholarshipApplicationStatus::Submitted,
                        ScholarshipApplicationStatus::UnderEvaluation,
                    ])
                    ->count(),
                'approved_records' => MasterlistRecord::query()
                    ->where('chairman_status', 'approved')
                    ->count(),
            ],
            'recentCertificateRequests' => CertificateRequest::query()
                ->with('student')
                ->latest()
                ->limit(5)
                ->get(),
            'recentMasterlists' => ScholarshipMasterlist::query()
                ->with('agency')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
