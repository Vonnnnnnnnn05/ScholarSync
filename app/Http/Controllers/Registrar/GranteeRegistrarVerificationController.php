<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\StagedGranteeRecord;
use App\Services\AuditTrailService;
use App\Services\GranteeBatchExcelExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GranteeRegistrarVerificationController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    /**
     * Display grantee batches waiting for or completed registrar verification.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->campus_id, 403, 'User does not have an assigned campus.');

        $batches = GranteeUploadBatch::query()
            ->with(['campus', 'uploader', 'sentToRegistrarBy'])
            ->where('campus_id', $user->campus_id)
            ->whereIn('status', ['sent_to_registrar', 'verified_by_registrar', 'endorsed_to_chairman'])
            ->latest('id')
            ->paginate(15);

        return view('registrar.grantees.index', [
            'batches' => $batches,
            'campus' => $user->campus,
        ]);
    }

    /**
     * Show grantee records in the batch for registrar verification and resolution.
     */
    public function show(GranteeUploadBatch $batch, Request $request): View
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $currentProgram = $request->string('program')->toString() ?: 'all';
        $currentStatus = $request->string('status')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());

        $query = $batch->stagedRecords()->with('matchedSisStudent');

        if ($currentProgram !== 'all') {
            $query->where('scholarship_program', $currentProgram);
        }

        if ($currentStatus !== 'all') {
            $query->where('verification_status', $currentStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('row_number')->paginate(50)->withQueryString();

        $categoryCounts = $batch->stagedRecords()
            ->selectRaw('scholarship_program, count(*) as count')
            ->groupBy('scholarship_program')
            ->pluck('count', 'scholarship_program')
            ->toArray();

        $campusStudentCount = RegistrarStudent::where('campus_id', $user->campus_id)->count();

        return view('registrar.grantees.show', [
            'batch' => $batch->load(['campus', 'uploader', 'deliveredToCoordinatorBy', 'sentToRegistrarBy', 'registrarVerifiedBy', 'coordinatorEndorsedBy']),
            'records' => $records,
            'categoryCounts' => $categoryCounts,
            'currentProgram' => $currentProgram,
            'currentStatus' => $currentStatus,
            'search' => $search,
            'campusStudentCount' => $campusStudentCount,
        ]);
    }

    /**
     * Registrar executes official enrollment verification against campus registrar_students repository.
     */
    public function verifyBatch(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $campusId = $user->campus_id;
        $campusStudents = RegistrarStudent::where('campus_id', $campusId)->get();

        // Index students for rapid matching by ID and normalized name
        $byId = [];
        $byName = [];

        foreach ($campusStudents as $student) {
            $normId = preg_replace('/[^a-zA-Z0-9]/', '', (string) $student->student_id_number);
            if ($normId !== '') {
                $byId[$normId] = $student;
            }

            $normName = $this->normalizeName($student->student_name);
            if ($normName !== '') {
                $byName[$normName] = $student;
            }
        }

        $records = $batch->stagedRecords()->get();

        DB::transaction(function () use ($records, $byId, $byName, $user) {
            foreach ($records as $record) {
                $matchedStudent = null;

                // 1. Try student ID match
                if (!empty($record->student_id)) {
                    $normId = preg_replace('/[^a-zA-Z0-9]/', '', (string) $record->student_id);
                    if (isset($byId[$normId])) {
                        $matchedStudent = $byId[$normId];
                    }
                }

                // 2. Try normalized name match
                if (!$matchedStudent && !empty($record->student_name)) {
                    $normName = $this->normalizeName($record->student_name);
                    if (isset($byName[$normName])) {
                        $matchedStudent = $byName[$normName];
                    }
                }

                if ($matchedStudent) {
                    $status = strtolower(trim((string) $matchedStudent->enrollment_status));
                    $corPrinted = (bool) $matchedStudent->cor_printed;

                    if ($status === 'enrolled' && $corPrinted) {
                        $record->update([
                            'verification_status' => 'GREEN_VERIFIED',
                            'matched_sis_student_id' => $matchedStudent->id,
                            'campus_id' => $matchedStudent->campus_id,
                            'campus' => $matchedStudent->campus,
                            'confidence_score' => 1.0,
                            'conflict_reason' => null,
                            'enrollment_status' => 'ENROLLED',
                            'remarks' => 'Eligible',
                        ]);
                    } elseif ($status === 'enrolled' && !$corPrinted) {
                        $record->update([
                            'verification_status' => 'AMBER_CONFLICT',
                            'matched_sis_student_id' => $matchedStudent->id,
                            'campus_id' => $matchedStudent->campus_id,
                            'campus' => $matchedStudent->campus,
                            'confidence_score' => 0.85,
                            'conflict_reason' => 'Enrolled but No COR Printed',
                            'enrollment_status' => 'NO_COR_PRINTED',
                            'remarks' => 'Not Eligible (No COR Printed)',
                        ]);
                    } else {
                        $record->update([
                            'verification_status' => 'RED_UNMATCHED',
                            'matched_sis_student_id' => $matchedStudent->id,
                            'campus_id' => $matchedStudent->campus_id,
                            'campus' => $matchedStudent->campus,
                            'confidence_score' => 0.0,
                            'conflict_reason' => 'Student Not Enrolled in Official SIS Repository',
                            'enrollment_status' => 'UNENROLLED',
                            'remarks' => 'Not Eligible (Not Enrolled)',
                        ]);
                    }
                } else {
                    $record->update([
                        'verification_status' => 'RED_UNMATCHED',
                        'matched_sis_student_id' => null,
                        'confidence_score' => 0.0,
                        'conflict_reason' => 'Student Not Found in Campus Repository',
                        'enrollment_status' => 'UNENROLLED',
                        'remarks' => 'Not Eligible (Not Found in Campus Repository)',
                    ]);
                }
            }
        });

        $batch->refreshCounters();
        $batch->update(['verified_at' => now()]);

        $this->auditTrail->record('grantee_batch_registrar_verified', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $campusId,
            'verified_by' => $user->id,
            'green_count' => $batch->green_count,
            'amber_count' => $batch->amber_count,
            'red_count' => $batch->red_count,
        ]);

        return back()->with('success', "Enrollment verification complete: {$batch->green_count} Verified (Enrolled with COR), {$batch->amber_count} Needs Review (No COR), {$batch->red_count} Unmatched.");
    }

    /**
     * Registrar manually updates/resolves a single record exception.
     */
    public function updateRecord(GranteeUploadBatch $batch, StagedGranteeRecord $record, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');
        abort_unless((int) $record->batch_id === (int) $batch->id, 404);

        $action = $request->input('action');

        if ($action === 'override_unmatched') {
            $record->update([
                'verification_status' => 'RED_UNMATCHED',
                'conflict_reason' => $request->string('resolution_notes')->toString() ?: 'Manually marked as unmatched by Registrar.',
                'enrollment_status' => 'UNENROLLED',
                'remarks' => 'Not Eligible',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->string('resolution_notes')->toString() ?: 'Manually marked as unmatched by Registrar.',
            ]);
        } else {
            $sisStudent = RegistrarStudent::where('campus_id', $user->campus_id)->findOrFail($request->integer('registrar_student_id'));
            $isCor = (bool) $sisStudent->cor_printed;
            $isEnrolled = $sisStudent->enrollment_status === 'enrolled';

            $record->update([
                'verification_status' => ($isEnrolled && $isCor) ? 'GREEN_VERIFIED' : 'AMBER_CONFLICT',
                'matched_sis_student_id' => $sisStudent->id,
                'campus_id' => $sisStudent->campus_id,
                'campus' => $sisStudent->campus,
                'program' => $sisStudent->course,
                'confidence_score' => 1.0,
                'conflict_reason' => (!$isCor && $isEnrolled) ? 'Enrolled but No COR Printed' : null,
                'enrollment_status' => ($isEnrolled && $isCor) ? 'ENROLLED' : ($isEnrolled ? 'NO_COR_PRINTED' : 'UNENROLLED'),
                'remarks' => ($isEnrolled && $isCor) ? 'Eligible' : 'Not Eligible',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
                'resolution_notes' => $request->string('resolution_notes')->toString() ?: 'Confirmed match by Registrar.',
            ]);
        }

        $batch->refreshCounters();

        $this->auditTrail->record('grantee_record_registrar_resolved', $record, [
            'batch_id' => $batch->id,
            'record_id' => $record->id,
            'status' => $record->verification_status,
            'resolved_by' => $user->id,
        ]);

        return back()->with('success', "Record #{$record->row_number} successfully resolved by Registrar.");
    }

    /**
     * Registrar returns the verified batch back to the Campus Coordinator.
     */
    public function returnToCoordinator(GranteeUploadBatch $batch, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $batch->update([
            'status' => 'verified_by_registrar',
            'registrar_verified_at' => now(),
            'registrar_verified_by' => $user->id,
            'registrar_notes' => $request->string('notes')->toString() ?: 'Campus Registrar verified official enrollment and COR status.',
        ]);

        $this->auditTrail->record('grantee_batch_returned_to_coordinator', $batch, [
            'batch_id' => $batch->id,
            'campus_id' => $user->campus_id,
            'returned_by' => $user->id,
            'notes' => $batch->registrar_notes,
        ]);

        return back()->with('success', "Batch #{$batch->id} verification finalized and returned to the Campus Coordinator.");
    }

    /**
     * Export the campus grantee records to Excel (.xlsx) format.
     */
    public function export(GranteeUploadBatch $batch, GranteeBatchExcelExportService $excelExporter, Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->campus_id && (int) $batch->campus_id === (int) $user->campus_id, 403, 'Unauthorized campus access.');

        return $excelExporter->export($batch);
    }

    private function normalizeName(?string $name): string
    {
        if (!$name) {
            return '';
        }
        $name = strtoupper($name);
        $name = preg_replace('/[^A-Z\s]/', ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
