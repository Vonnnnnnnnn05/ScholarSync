<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.monitoring.audit', [
            'auditLogs' => AuditLog::query()
                ->with('user')
                ->latest()
                ->paginate(20),
        ]);
    }
}
