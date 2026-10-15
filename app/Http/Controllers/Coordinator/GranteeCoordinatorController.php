<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\GranteeUploadBatch;
use App\Models\StagedGranteeRecord;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GranteeCoordinatorController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    /**
     * Display all external agency grantee batches assigned to this campus.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->campus_id, 403, 'User does not have an assigned campus.');

        $batches = GranteeUploadBatch::query()
            ->with(['campus', 'uploader'])
            ->where('campus_id', $user->campus_id)
            ->latest('id')
            ->paginate(15);

        return view('coordinator.grantees.index', [
            'batches' => $batches,
            'campus' => $user->campus,
        ]);
    }

    /**
     * Review the grantee records for this campus.
     */
    public function show(GranteeUploadBatch $batch, Request $request): View
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $currentProgram = $request->string('program')->toString() ?: 'all';
        $currentStatus = $request->string('status')->toString() ?: 'all';

        $query = $batch->stagedRecords()->with('matchedSisStudent');

        if ($currentProgram !== 'all') {
            $query->where('scholarship_program', $currentProgram);
        }

        if ($currentStatus !== 'all') {
            $query->where('verification_status', $currentStatus);
        }

        $records = $query->orderBy('row_number')->paginate(50)->withQueryString();

        // Calculate dynamic program categories in batch
        $categoryCounts = $batch->stagedRecords()
            ->selectRaw('scholarship_program, count(*) as count')
            ->groupBy('scholarship_program')
            ->pluck('count', 'scholarship_program')
            ->toArray();

        return view('coordinator.grantees.show', [
            'batch' => $batch->load(['campus', 'uploader', 'deliveredToCoordinatorBy', 'sentToRegistrarBy', 'registrarVerifiedBy', 'coordinatorEndorsedBy']),
            'records' => $records,
            'categoryCounts' => $categoryCounts,
            'currentProgram' => $currentProgram,
            'currentStatus' => $currentStatus,
        ]);
    }

    /**
     * Coordinator reviews and forwards the batch to Campus Registrar for official enrollment verification.
     */
    public function forwardToRegistrar(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch->update([
            'status' => 'sent_to_registrar',
            'sent_to_registrar_at' => now(),
            'sent_to_registrar_by' => $user->id,
            'coordinator_to_registrar_notes' => $request->string('notes')->toString() ?: 'Forwarded to Campus Registrar for official enrollment verification.',
        ]);

        $this->auditTrail->record('grantee_batch_forwarded_to_registrar', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $user->campus_id,
            'forwarded_by' => $user->id,
            'notes' => $batch->coordinator_to_registrar_notes,
        ]);

        return back()->with('success', "Batch #{$batch->id} has been forwarded to the Campus Registrar for official enrollment verification.");
    }

    /**
     * Coordinator passes the verified batch back to the Scholarship Chairman.
     */
    public function endorse(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch->update([
            'status' => 'endorsed_to_chairman',
            'coordinator_endorsed_at' => now(),
            'coordinator_endorsed_by' => $user->id,
            'coordinator_notes' => $request->string('notes')->toString() ?: 'Campus Coordinator passed verified beneficiary roster to Chairman.',
        ]);

        $this->auditTrail->record('grantee_batch_coordinator_endorsed', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $user->campus_id,
            'endorsed_by' => $user->id,
            'notes' => $batch->coordinator_notes,
        ]);

        return back()->with('success', "Batch #{$batch->id} successfully passed to the Scholarship Chairman.");
    }

    /**
     * Export the campus grantee records to Excel (.xlsx) format.
     */
    public function export(GranteeUploadBatch $batch, \App\Services\GranteeBatchExcelExportService $excelExporter, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        return $excelExporter->export($batch);
    }
}
