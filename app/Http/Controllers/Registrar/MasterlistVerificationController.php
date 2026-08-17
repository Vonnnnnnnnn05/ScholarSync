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
use Illuminate\View\View;

class MasterlistVerificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('registrar.masterlists.index', ['batches' => MasterlistCampusBatch::query()
            ->with(['masterlist.agency', 'campus'])
            ->where('campus_id', $request->user()->campus_id)->where('status', 'with_registrar')->latest()->paginate(10)]);
    }

    public function show(Request $request, MasterlistCampusBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);
        abort_unless(in_array($batch->status, ['with_registrar', 'returned_to_coordinator'], true), 404);
        $records = $batch->records()->with('registrarStudent')->oldest('id')->paginate(20);
        $records->getCollection()->each(function (MasterlistRecord $record) use ($batch): void {
            if (! $record->registrar_student_id) {
                $match = RegistrarStudent::query()->where('campus_id', $batch->campus_id)->where('student_name', $record->student_name)->first();
                if ($match) {
                    $record->setRelation('registrarStudent', $match);
                }
            }
        });

        return view('registrar.masterlists.show', compact('batch', 'records'));
    }

    public function update(UpdateRegistrarMasterlistRecordRequest $request, MasterlistCampusBatch $batch, MasterlistRecord $record, AuditTrailService $audit): RedirectResponse
    {
        $this->authorizeBatch($request, $batch);
        abort_unless($batch->status === 'with_registrar' && $record->masterlist_id === $batch->masterlist_id && $record->campus_id === $batch->campus_id, 404);
        $record->update($request->validated() + ['verified_by' => $request->user()->id, 'verified_at' => now()]);
        $audit->record('masterlist_record_registrar_verified', $record, ['verification_status' => $record->verification_status], $request);

        return back()->with('status', 'Verification result saved.');
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
