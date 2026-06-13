<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCoordinatorMasterlistRecordRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterlistValidationController extends Controller
{
    public function index(): View
    {
        return view('coordinator.masterlists.index', [
            'masterlists' => ScholarshipMasterlist::query()
                ->with('agency')
                ->withCount([
                    'records',
                    'records as pending_records_count' => fn ($query) => $query->where('coordinator_status', 'pending'),
                    'records as reviewed_records_count' => fn ($query) => $query->where('coordinator_status', '!=', 'pending'),
                ])
                ->whereIn('status', ['verified', 'coordinator_validation'])
                ->latest('validated_at')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, ScholarshipMasterlist $masterlist): View
    {
        abort_unless(in_array($masterlist->status, ['verified', 'coordinator_validation', 'submitted_to_chairman'], true), 404);

        $activeStatus = $request->string('status')->toString();
        $recordsQuery = $masterlist->records()
            ->with('matchedStudent')
            ->when(
                in_array($activeStatus, ['enrolled', 'unenrolled', 'duplicate', 'invalid'], true),
                fn ($query) => $query->where('verification_status', $activeStatus),
            )
            ->oldest('id');

        return view('coordinator.masterlists.show', [
            'masterlist' => $masterlist->load('agency'),
            'records' => $recordsQuery->paginate(20)->withQueryString(),
            'activeStatus' => $activeStatus,
            'verificationStatuses' => ['enrolled', 'unenrolled', 'duplicate', 'invalid'],
            'canEdit' => $masterlist->status !== 'submitted_to_chairman',
        ]);
    }

    public function updateRecord(
        UpdateCoordinatorMasterlistRecordRequest $request,
        ScholarshipMasterlist $masterlist,
        MasterlistRecord $record,
        AuditTrailService $audit,
    ): RedirectResponse {
        abort_unless($record->masterlist_id === $masterlist->id, 404);
        abort_unless(in_array($masterlist->status, ['verified', 'coordinator_validation'], true), 404);

        $record->update($request->validated());

        if ($masterlist->status === 'verified') {
            $masterlist->update(['status' => 'coordinator_validation']);
        }

        $audit->record('masterlist_record_coordinator_validated', $record, [
            'masterlist_id' => $masterlist->id,
            'coordinator_status' => $record->coordinator_status,
        ], $request);

        return back()->with('status', 'Record validation saved.');
    }

    public function submit(ScholarshipMasterlist $masterlist, AuditTrailService $audit): RedirectResponse
    {
        abort_unless(in_array($masterlist->status, ['verified', 'coordinator_validation'], true), 404);

        $pendingRecords = $masterlist->records()
            ->where('coordinator_status', 'pending')
            ->count();

        if ($pendingRecords > 0) {
            return back()->withErrors([
                'submit' => 'Review all records before submitting this masterlist to the chairman.',
            ]);
        }

        DB::transaction(function () use ($masterlist): void {
            $masterlist->records()
                ->where('coordinator_status', 'for_chairman_review')
                ->update(['chairman_status' => 'pending']);

            $masterlist->update([
                'status' => 'submitted_to_chairman',
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);
        });

        $audit->record('masterlist_submitted_to_chairman', $masterlist, [
            'records' => $masterlist->records()->count(),
        ]);

        return redirect()
            ->route('coordinator.masterlists.show', $masterlist)
            ->with('status', 'Masterlist submitted to the scholarship chairman.');
    }
}
