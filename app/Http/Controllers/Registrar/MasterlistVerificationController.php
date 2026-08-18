<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRegistrarMasterlistRecordRequest;
use App\Models\MasterlistCampusBatch;
use App\Models\MasterlistRecord;
use App\Models\RegistrarStudent;
use App\Services\AuditTrailService;
use App\Services\MasterlistCampusWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterlistVerificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('registrar.masterlists.index', ['batches' => MasterlistCampusBatch::query()
            ->with(['masterlist', 'campus'])
            ->where('campus_id', $request->user()->campus_id)
            ->whereIn('status', ['verification_queued', 'verification_processing', 'awaiting_registrar_review', 'verification_failed'])
            ->latest()->paginate(10)]);
    }

    public function show(Request $request, MasterlistCampusBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);
        abort_unless(in_array($batch->status, ['verification_queued', 'verification_processing', 'awaiting_registrar_review', 'verification_failed', 'returned_to_coordinator'], true), 404);
        $filter = $request->string('filter')->toString();
        $records = $batch->records()->with(['registrarStudent', 'resolver'])
            ->when($filter === 'needs_review', fn ($query) => $query->where('final_qualification_status', 'needs_review'))
            ->when($filter === 'not_enrolled', fn ($query) => $query->where('final_enrollment_status', 'not_enrolled'))
            ->when($filter === 'no_cor_printed', fn ($query) => $query->where('final_cor_status', 'no_cor_printed'))
            ->when($filter === 'not_qualified', fn ($query) => $query->where('final_qualification_status', 'not_qualified'))
            ->when($filter === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->oldest('id')->paginate(20)->withQueryString();
        $records->getCollection()->each(function (MasterlistRecord $record) use ($batch): void {
            if (! $record->registrar_student_id) {
                $match = RegistrarStudent::query()->where('campus_id', $batch->campus_id)->where('student_name', $record->student_name)->first();
                if ($match) {
                    $record->setRelation('registrarStudent', $match);
                }
            }
        });

        $summary = [
            'total' => $batch->records()->count(),
            'qualified' => $batch->records()->where('final_qualification_status', 'qualified')->count(),
            'not_qualified' => $batch->records()->where('final_qualification_status', 'not_qualified')->count(),
            'needs_review' => $batch->records()->where('final_qualification_status', 'needs_review')->count(),
            'not_enrolled' => $batch->records()->where('final_enrollment_status', 'not_enrolled')->count(),
            'no_cor_printed' => $batch->records()->where('final_cor_status', 'no_cor_printed')->count(),
        ];

        return view('registrar.masterlists.show', compact('batch', 'records', 'summary', 'filter'));
    }

    public function update(UpdateRegistrarMasterlistRecordRequest $request, MasterlistCampusBatch $batch, MasterlistRecord $record, AuditTrailService $audit): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        abort_unless($batch->status === 'awaiting_registrar_review' && $record->masterlist_id === $batch->masterlist_id && $record->campus_id === $batch->campus_id, 404);
        $data = $request->validated();
        $previous = $record->only(['final_enrollment_status', 'final_cor_status', 'final_qualification_status']);
        DB::transaction(function () use ($record, $request, $data, $previous): void {
            $record->update([
                'final_enrollment_status' => $data['final_enrollment_status'],
                'final_cor_status' => $data['final_cor_status'],
                'final_qualification_status' => $data['final_qualification_status'],
                'verification_status' => $data['final_qualification_status'] === 'qualified' ? 'verified' : 'not_verified',
                'remarks' => $data['reason'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $record->registrarResolutions()->create([
                'registrar_id' => $request->user()->id,
                'previous_results' => $previous,
                'new_results' => $record->only(['final_enrollment_status', 'final_cor_status', 'final_qualification_status']),
                'reason' => $data['reason'],
            ]);
        });
        $audit->record('masterlist_record_registrar_resolved', $record, ['previous' => $previous, 'final_qualification_status' => $record->final_qualification_status], $request);

        return back()->with('status', 'Exception resolution saved.');
    }

    public function return(Request $request, MasterlistCampusBatch $batch, MasterlistCampusWorkflowService $workflow): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        $workflow->returnToCoordinator($batch, $request->user());

        return back()->with('status', 'Verification results returned to the Campus Coordinator.');
    }

    private function authorizeBatch(Request $request, MasterlistCampusBatch $batch): void
    {
        abort_unless($request->user()->campus_id && $request->user()->campus_id === $batch->campus_id, 403);
    }
}
