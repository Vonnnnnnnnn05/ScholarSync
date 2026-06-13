<?php

namespace App\Http\Controllers\Student;

use App\Enums\ScholarshipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScholarshipRenewalRequest;
use App\Models\ScholarshipApplication;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipRenewalController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;

        return view('student.scholarship-renewals.index', [
            'applications' => ScholarshipApplication::query()
                ->with('requirements')
                ->when($student, fn ($query) => $query->where('student_id', $student->id))
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('student.scholarship-renewals.create', [
            'requiredDocuments' => StoreScholarshipRenewalRequest::REQUIRED_DOCUMENTS,
        ]);
    }

    public function store(StoreScholarshipRenewalRequest $request): RedirectResponse
    {
        $application = DB::transaction(function () use ($request): ScholarshipApplication {
            $student = $this->studentFor($request);

            $application = $student->scholarshipApplications()->create([
                'scholarship_program' => $request->validated('scholarship_program'),
                'fund_source' => $request->validated('fund_source'),
                'status' => ScholarshipApplicationStatus::Submitted,
            ]);

            $this->storeRequirements($application, $request);

            return $application;
        });

        return redirect()
            ->route('student.scholarship-renewals.show', $application)
            ->with('status', 'Scholarship renewal requirements submitted successfully.');
    }

    public function show(Request $request, ScholarshipApplication $application): View
    {
        $this->ensureOwnsApplication($request, $application);

        return view('student.scholarship-renewals.show', [
            'application' => $application->load(['student', 'requirements', 'evaluator']),
            'requiredDocuments' => StoreScholarshipRenewalRequest::REQUIRED_DOCUMENTS,
        ]);
    }

    public function revise(
        StoreScholarshipRenewalRequest $request,
        ScholarshipApplication $application,
    ): RedirectResponse {
        $this->ensureOwnsApplication($request, $application);

        abort_unless($application->canBeRevisedByStudent(), 404);

        DB::transaction(function () use ($request, $application): void {
            foreach ($application->requirements as $requirement) {
                Storage::disk('local')->delete($requirement->file_path);
            }

            $application->requirements()->delete();
            $application->update([
                'scholarship_program' => $request->validated('scholarship_program'),
                'fund_source' => $request->validated('fund_source'),
                'status' => ScholarshipApplicationStatus::Submitted,
                'remarks' => null,
                'evaluated_by' => null,
                'evaluated_at' => null,
            ]);

            $this->storeRequirements($application, $request);
        });

        return redirect()
            ->route('student.scholarship-renewals.show', $application)
            ->with('status', 'Revised scholarship requirements resubmitted successfully.');
    }

    public function downloadRequirement(
        Request $request,
        ScholarshipApplication $application,
        int $requirement,
    ): StreamedResponse {
        $this->ensureOwnsApplication($request, $application);

        $requirement = $application->requirements()->findOrFail($requirement);

        abort_unless(Storage::disk('local')->exists($requirement->file_path), 404);

        return Storage::disk('local')->download($requirement->file_path);
    }

    private function storeRequirements(ScholarshipApplication $application, StoreScholarshipRenewalRequest $request): void
    {
        foreach (StoreScholarshipRenewalRequest::REQUIRED_DOCUMENTS as $field => $label) {
            $application->requirements()->create([
                'requirement_name' => $label,
                'file_path' => $request->file($field)->store('scholarship-renewals/requirements', 'local'),
                'status' => 'submitted',
            ]);
        }
    }

    private function studentFor(Request $request): Student
    {
        $nameParts = str($request->user()->name)->explode(' ');

        return Student::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'student_id_number' => 'TEMP-'.$request->user()->id,
                'first_name' => $nameParts->first() ?: $request->user()->name,
                'middle_name' => null,
                'last_name' => $nameParts->count() > 1 ? $nameParts->last() : 'Student',
                'status' => 'active',
            ],
        );
    }

    private function ensureOwnsApplication(Request $request, ScholarshipApplication $application): void
    {
        abort_unless(
            $application->student()->where('user_id', $request->user()->id)->exists(),
            404
        );
    }
}
