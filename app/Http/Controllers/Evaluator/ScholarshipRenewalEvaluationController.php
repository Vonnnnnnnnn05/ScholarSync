<?php

namespace App\Http\Controllers\Evaluator;

use App\Enums\ScholarshipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluateScholarshipRenewalRequest;
use App\Mail\ScholarshipEvaluationResultMail;
use App\Models\ScholarshipApplication;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipRenewalEvaluationController extends Controller
{
    public function index(Request $request): View
    {
        $activeStatus = $request->string('status')->toString();

        return view('evaluator.scholarship-renewals.index', [
            'applications' => ScholarshipApplication::query()
                ->with(['student.user', 'requirements'])
                ->when(
                    in_array($activeStatus, ScholarshipApplicationStatus::values(), true),
                    fn ($query) => $query->where('status', $activeStatus),
                )
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'statuses' => ScholarshipApplicationStatus::cases(),
            'activeStatus' => $activeStatus,
        ]);
    }

    public function show(ScholarshipApplication $application): View
    {
        return view('evaluator.scholarship-renewals.show', [
            'application' => $application->load(['student.user', 'requirements', 'evaluator']),
            'evaluationStatuses' => [
                ScholarshipApplicationStatus::UnderEvaluation,
                ScholarshipApplicationStatus::Approved,
                ScholarshipApplicationStatus::Rejected,
                ScholarshipApplicationStatus::NeedRevision,
            ],
        ]);
    }

    public function update(
        EvaluateScholarshipRenewalRequest $request,
        ScholarshipApplication $application,
    ): RedirectResponse {
        $application->update([
            'status' => ScholarshipApplicationStatus::from($request->validated('status')),
            'remarks' => $request->validated('remarks'),
            'evaluated_by' => $request->user()->id,
            'evaluated_at' => now(),
        ]);

        if (in_array($application->status, [
            ScholarshipApplicationStatus::Approved,
            ScholarshipApplicationStatus::Rejected,
            ScholarshipApplicationStatus::NeedRevision,
        ], true)) {
            $this->notifyStudent($application->refresh());
        }

        return back()->with('status', 'Scholarship renewal evaluation saved.');
    }

    public function downloadRequirement(
        ScholarshipApplication $application,
        int $requirement,
    ): StreamedResponse {
        $requirement = $application->requirements()->findOrFail($requirement);

        abort_unless(Storage::disk('local')->exists($requirement->file_path), 404);

        return Storage::disk('local')->download($requirement->file_path);
    }

    private function notifyStudent(ScholarshipApplication $application): void
    {
        $application->loadMissing('student.user');

        UserNotification::create([
            'user_id' => $application->student->user_id,
            'title' => 'Scholarship Renewal '.$application->status->label(),
            'message' => $application->remarks ?: 'Your scholarship renewal evaluation has been updated.',
            'type' => 'scholarship_renewal',
            'status' => 'unread',
        ]);

        Mail::to($application->student->user->email)
            ->send(new ScholarshipEvaluationResultMail($application));
    }
}
