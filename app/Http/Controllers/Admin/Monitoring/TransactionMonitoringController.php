<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use Illuminate\View\View;

class TransactionMonitoringController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.monitoring.transactions', [
            'certificateRequests' => CertificateRequest::query()->with('student')->latest()->limit(10)->get(),
            'masterlists' => ScholarshipMasterlist::query()->with('agency')->latest()->limit(10)->get(),
            'evaluations' => ScholarshipApplication::query()->with(['student', 'evaluator'])->latest()->limit(10)->get(),
        ]);
    }
}
