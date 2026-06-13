<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\MasterlistRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScholarRecordMonitoringController extends Controller
{
    public function __invoke(Request $request): View
    {
        $status = $request->string('status')->toString();

        return view('admin.monitoring.scholars', [
            'records' => MasterlistRecord::query()
                ->with(['masterlist.agency', 'matchedStudent'])
                ->when($status !== '', fn ($query) => $query->where('chairman_status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'activeStatus' => $status,
        ]);
    }
}
