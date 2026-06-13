<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChairmanMasterlistRecordRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterlistApprovalController extends Controller
{
    public function index(): View
    {
        return view('chairman.masterlists.index', [
            'masterlists' => ScholarshipMasterlist::query()
                ->with('agency')
                ->withCount([
                    'records',
                    'records as pending_records_count' => fn ($query) => $query->where('chairman_status', 'pending'),
                    'records as approved_records_count' => fn ($query) => $query->where('chairman_status', 'approved'),
                    'records as rejected_records_count' => fn ($query) => $query->where('chairman_status', 'rejected'),
                ])
                ->whereIn('status', ['submitted_to_chairman', 'chairman_review'])
                ->latest('validated_at')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, ScholarshipMasterlist $masterlist): View
    {
        abort_unless(in_array($masterlist->status, ['submitted_to_chairman', 'chairman_review', 'released'], true), 404);

        $activeStatus = $request->string('status')->toString();
        $recordsQuery = $masterlist->records()
            ->with('matchedStudent')
            ->when(
                in_array($activeStatus, ['enrolled', 'unenrolled', 'duplicate', 'invalid'], true),
                fn ($query) => $query->where('verification_status', $activeStatus),
            )
            ->oldest('id');

        return view('chairman.masterlists.show', [
            'masterlist' => $masterlist->load(['agency', 'agency.user']),
            'records' => $recordsQuery->paginate(20)->withQueryString(),
            'activeStatus' => $activeStatus,
            'verificationStatuses' => ['enrolled', 'unenrolled', 'duplicate', 'invalid'],
            'canEdit' => $masterlist->status !== 'released',
        ]);
    }

    public function updateRecord(
        UpdateChairmanMasterlistRecordRequest $request,
        ScholarshipMasterlist $masterlist,
        MasterlistRecord $record,
        AuditTrailService $audit,
    ): RedirectResponse {
        abort_unless($record->masterlist_id === $masterlist->id, 404);
        abort_unless(in_array($masterlist->status, ['submitted_to_chairman', 'chairman_review'], true), 404);

        $record->update($request->validated());

        if ($masterlist->status === 'submitted_to_chairman') {
            $masterlist->update(['status' => 'chairman_review']);
        }

        $audit->record('masterlist_record_chairman_decision', $record, [
            'masterlist_id' => $masterlist->id,
            'chairman_status' => $record->chairman_status,
        ], $request);

        return back()->with('status', 'Chairman decision saved.');
    }

    public function release(ScholarshipMasterlist $masterlist, AuditTrailService $audit): RedirectResponse
    {
        abort_unless(in_array($masterlist->status, ['submitted_to_chairman', 'chairman_review'], true), 404);

        $pendingRecords = $masterlist->records()
            ->where('chairman_status', 'pending')
            ->count();

        if ($pendingRecords > 0) {
            return back()->withErrors([
                'release' => 'Review all records before releasing the final scholar records.',
            ]);
        }

        DB::transaction(function () use ($masterlist): void {
            $masterlist->update([
                'status' => 'released',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        $audit->record('masterlist_released', $masterlist, [
            'approved_records' => $masterlist->records()->where('chairman_status', 'approved')->count(),
            'rejected_records' => $masterlist->records()->where('chairman_status', 'rejected')->count(),
        ]);

        return redirect()
            ->route('chairman.masterlists.show', $masterlist)
            ->with('status', 'Final scholar records released to the scholarship agency.');
    }
}
