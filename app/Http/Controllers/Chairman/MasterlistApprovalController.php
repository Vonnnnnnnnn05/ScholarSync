<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChairmanMasterlistRecordRequest;
use App\Models\Campus;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Services\AuditTrailService;
use App\Services\VerifiedMasterlistExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterlistApprovalController extends Controller
{
    public function index(): View
    {
        return view('chairman.masterlists.index', [
            'masterlists' => ScholarshipMasterlist::query()
                ->withCount([
                    'records',
                    'campusBatches',
                    'campusBatches as completed_campus_batches_count' => fn ($query) => $query->where('status', 'submitted_to_chairman'),
                    'records as pending_records_count' => fn ($query) => $query->where('chairman_status', 'pending'),
                    'records as approved_records_count' => fn ($query) => $query->where('chairman_status', 'approved'),
                    'records as rejected_records_count' => fn ($query) => $query->where('chairman_status', 'rejected'),
                ])
                ->whereIn('status', ['distributed', 'campus_verification', 'submitted_to_chairman', 'chairman_review', 'ready_for_consolidation', 'released'])
                ->latest('validated_at')
                ->paginate(10),
        ]);
    }

    public function show(Request $request, ScholarshipMasterlist $masterlist): View
    {
        abort_unless(in_array($masterlist->status, ['distributed', 'campus_verification', 'submitted_to_chairman', 'chairman_review', 'ready_for_consolidation', 'released'], true), 404);

        $activeStatus = $request->string('status')->toString();
        $recordsQuery = $masterlist->records()
            ->with('matchedStudent')
            ->when(
                in_array($activeStatus, ['enrolled', 'no_cor_printed', 'unenrolled'], true),
                fn ($query) => $query->where('verification_status', $activeStatus),
            )
            ->oldest('id');

        return view('chairman.masterlists.show', [
            'masterlist' => $masterlist,
            'records' => $recordsQuery->paginate(20)->withQueryString(),
            'activeStatus' => $activeStatus,
            'verificationStatuses' => ['enrolled', 'no_cor_printed', 'unenrolled'],
            'canEdit' => $masterlist->status !== 'released',
            'campusProgress' => Campus::query()->where('is_active', true)->orderBy('name')->get()->map(function (Campus $campus) use ($masterlist): array {
                $batch = $masterlist->campusBatches()->where('campus_id', $campus->id)->first();

                return ['campus' => $campus, 'status' => $batch?->status ?? 'no_records', 'records' => $batch?->records()->count() ?? 0];
            }),
        ]);
    }

    public function export(ScholarshipMasterlist $masterlist, VerifiedMasterlistExportService $exporter): StreamedResponse
    {
        abort_unless(in_array($masterlist->status, ['ready_for_consolidation', 'released'], true), 422);

        return $exporter->download($masterlist);
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
        if ($masterlist->campusBatches()->doesntExist() || $masterlist->campusBatches()->where('status', '!=', 'submitted_to_chairman')->exists()) {
            return back()->withErrors([
                'release' => 'Every represented campus must submit its Registrar-verified batch before release.',
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
            'verified_records' => $masterlist->records()->where('verification_status', 'verified')->count(),
            'not_verified_records' => $masterlist->records()->where('verification_status', 'not_verified')->count(),
        ]);

        return redirect()
            ->route('chairman.masterlists.show', $masterlist)
            ->with('status', 'Final verified beneficiary list marked as forwarded to the external agency.');
    }
}
