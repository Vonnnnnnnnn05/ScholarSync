<?php

namespace App\Http\Controllers\Chairman;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyGranteeChunk;
use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\StagedGranteeRecord;
use App\Services\AuditTrailService;
use App\Services\GranteeCommitService;
use App\Services\GranteeVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GranteeReconciliationController extends Controller
{
    public function __construct(
        protected GranteeVerificationService $verificationService,
        protected GranteeCommitService $commitService,
        protected AuditTrailService $auditTrail
    ) {}

    /**
     * List all grantee upload batches.
     */
    public function index(): View
    {
        $batches = GranteeUploadBatch::query()
            ->with(['uploader', 'committer', 'campus'])
            ->latest()
            ->paginate(15);

        return view('chairman.grantees.index', compact('batches'));
    }

    /**
     * Show reconciliation dashboard for a specific batch.
     */
    public function show(GranteeUploadBatch $batch, Request $request): View
    {
        $statusFilter = $request->query('status');
        $programFilter = $request->query('program');

        $query = $batch->stagedRecords()->with(['campus', 'matchedSisStudent']);

        if ($statusFilter && in_array($statusFilter, ['GREEN_VERIFIED', 'AMBER_CONFLICT', 'RED_UNMATCHED', 'PENDING'], true)) {
            $query->where('verification_status', $statusFilter);
        }

        if ($programFilter && $programFilter !== 'all') {
            $query->where(function ($q) use ($programFilter) {
                $q->where('scholarship_program', $programFilter)
                  ->orWhere('scholarship_name', $programFilter);
            });
        }

        $records = $query->orderBy('row_number')->paginate(50)->withQueryString();

        // Calculate program counts for category tabs
        $programStats = $batch->stagedRecords()
            ->selectRaw("COALESCE(scholarship_program, scholarship_name, 'Other') as prog, count(*) as count")
            ->groupBy('prog')
            ->pluck('count', 'prog')
            ->all();

        $unassignedCampusCount = $batch->stagedRecords()->whereNull('campus_id')->count();

        return view('chairman.grantees.reconciliation', [
            'batch' => $batch->load([
                'uploader',
                'committer',
                'campus',
                'deliveredToCoordinatorBy',
                'sentToRegistrarBy',
                'registrarVerifiedBy',
                'coordinatorEndorsedBy',
            ]),
            'records' => $records,
            'currentFilter' => $statusFilter ?: 'all',
            'currentProgram' => $programFilter ?: 'all',
            'programStats' => $programStats,
            'campuses' => \App\Models\Campus::where('is_active', true)->orderBy('name')->get(),
            'unassignedCampusCount' => $unassignedCampusCount,
        ]);
    }

    /**
     * Trigger verification for all pending records in the batch.
     */
    public function verify(GranteeUploadBatch $batch): RedirectResponse
    {
        if ($batch->status === 'committed') {
            return back()->with('error', 'Committed batches cannot be re-verified.');
        }

        $this->auditTrail->record('batch_verification_started', $batch, [
            'batch_id' => $batch->id,
            'total_rows' => $batch->total_rows,
        ]);

        try {
            $result = $this->verificationService->verifyBatchOrChunk($batch);
            return back()->with('success', "Verification completed! ({$result['green_count']} Green, {$result['amber_count']} Amber, {$result['red_count']} Red).");
        } catch (\Exception $e) {
            return back()->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Assign campus to an individual staged grantee record and deliver to Campus Coordinator.
     */
    public function assignCampus(GranteeUploadBatch $batch, StagedGranteeRecord $record, Request $request): RedirectResponse
    {
        if ($record->batch_id !== $batch->id) {
            abort(404);
        }

        $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
        ]);

        $campus = \App\Models\Campus::findOrFail($request->integer('campus_id'));
        $record->update([
            'campus_id' => $campus->id,
            'campus' => $campus->name,
            'remarks' => "Assigned to {$campus->name} (routed to Campus Coordinator)",
        ]);

        $batch->refreshCounters();

        $this->auditTrail->record('grantee_campus_assigned', $record, [
            'batch_id' => $batch->id,
            'record_id' => $record->id,
            'campus_id' => $campus->id,
            'assigned_by' => $request->user()->id,
        ]);

        return back()->with('success', "Record #{$record->row_number} assigned to {$campus->name} and routed to Campus Coordinator.");
    }

    /**
     * Assign a fallback campus to records in the batch and deliver to Campus Coordinator.
     */
    public function assignFallbackCampus(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'apply_scope' => ['nullable', 'string', 'in:unassigned,all'],
        ]);

        $campus = \App\Models\Campus::findOrFail($request->integer('campus_id'));
        $applyScope = $request->input('apply_scope', 'unassigned');

        $query = $batch->stagedRecords();
        if ($applyScope !== 'all') {
            $query->whereNull('campus_id');
        }

        $affected = $query->update([
            'campus_id' => $campus->id,
            'campus' => $campus->name,
            'remarks' => "Bulk assigned to {$campus->name} (routed to Campus Coordinator)",
        ]);

        $batch->update(['campus_id' => $campus->id]);
        $batch->refreshCounters();

        $this->auditTrail->record('grantee_fallback_campus_assigned', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $campus->id,
            'apply_scope' => $applyScope,
            'affected_records' => $affected,
            'assigned_by' => $request->user()->id,
        ]);

        $scopeMsg = $applyScope === 'all' ? "All {$affected} records in this batch" : "{$affected} unassigned records";
        return back()->with('success', "{$scopeMsg} successfully assigned to {$campus->name} and routed to Campus Coordinator.");
    }

    /**
     * Resolve an amber conflict row by assigning a confirmed student.
     */
    public function resolve(GranteeUploadBatch $batch, StagedGranteeRecord $record, Request $request): RedirectResponse
    {
        if ($record->batch_id !== $batch->id) {
            abort(404);
        }

        $request->validate([
            'action' => ['required', 'in:confirm_match,mark_unmatched'],
            'registrar_student_id' => ['required_if:action,confirm_match', 'nullable', 'integer', 'exists:registrar_students,id'],
            'resolution_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->string('action')->toString() === 'mark_unmatched') {
            $record->update([
                'verification_status' => 'RED_UNMATCHED',
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->string('resolution_notes')->toString() ?: 'Manually marked as unmatched by Chairman.',
            ]);
        } else {
            $sisStudent = RegistrarStudent::findOrFail($request->integer('registrar_student_id'));
            $record->update([
                'verification_status' => 'GREEN_VERIFIED',
                'matched_sis_student_id' => $sisStudent->id,
                'campus_id' => $sisStudent->campus_id,
                'program' => $sisStudent->course,
                'confidence_score' => 1.0,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->string('resolution_notes')->toString() ?: 'Confirmed match by Chairman.',
            ]);
        }

        $batch->refreshCounters();

        $this->auditTrail->record('record_resolved', $record, [
            'batch_id' => $batch->id,
            'record_id' => $record->id,
            'status' => $record->verification_status,
            'resolved_by' => $request->user()->id,
        ]);

        return back()->with('success', "Record #{$record->row_number} successfully resolved.");
    }

    /**
     * Commit the batch: flag verified students as grantees.
     */
    public function commit(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        try {
            $committedCount = $this->commitService->commitBatch($batch, $request->user());
            return redirect()->route('chairman.grantees.show', $batch)
                ->with('success', "Batch successfully committed! {$committedCount} students are now officially flagged as active scholarship grantees.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX student search for resolving amber rows.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('query'));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $students = RegistrarStudent::query()
            ->with('campus')
            ->where('student_name', 'like', "%{$q}%")
            ->orWhere('student_id_number', 'like', "%{$q}%")
            ->limit(10)
            ->get(['id', 'student_id_number', 'student_name', 'campus_id', 'course', 'year_level', 'enrollment_status']);

        return response()->json($students);
    }

    /**
     * Officially deliver/send the batch to the designated Campus Scholarship Coordinator.
     */
    public function deliverToCoordinator(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        if (!$batch->campus_id) {
            return back()->with('error', 'Please assign a target campus destination before delivering to the Campus Coordinator.');
        }

        $campus = $batch->campus;
        $batch->update([
            'status' => 'delivered_to_coordinator',
            'delivered_to_coordinator_at' => now(),
            'delivered_to_coordinator_by' => $request->user()->id,
        ]);

        $this->auditTrail->record('grantee_batch_delivered_to_coordinator', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $campus->id,
            'campus_name' => $campus->name,
            'total_rows' => $batch->total_rows,
            'delivered_by' => $request->user()->id,
        ]);

        $coordinatorEmail = "coordinator.{$campus->code}@scholarsync.test";
        return back()->with('success', "Batch #{$batch->id} has been officially sent and delivered to {$campus->name} Scholarship Coordinator ({$coordinatorEmail}).");
    }

    /**
     * Export the verified batch records to Excel (.xlsx) spreadsheet format.
     */
    public function export(GranteeUploadBatch $batch, \App\Services\GranteeBatchExcelExportService $excelExporter): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $excelExporter->export($batch);
    }
}
