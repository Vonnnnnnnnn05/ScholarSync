<?php

namespace App\Services;

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRequirement;
use App\Models\Student;
use Illuminate\Support\Collection;

class ReportBuilderService
{
    /**
     * @return array<string, string>
     */
    public function types(): array
    {
        return [
            'scholar_information' => 'Scholar Information Report',
            'certificate_requests' => 'Certificate Request Report',
            'or_verification' => 'Official Receipt Verification Report',
            'masterlists' => 'Scholarship Masterlist Report',
            'renewal_evaluations' => 'Continuing Scholarship Evaluation Report',
            'requirement_submissions' => 'Student Requirement Submission Report',
            'fund_sources' => 'Scholarship Fund Source Report',
            'approved_rejected' => 'Approved and Rejected Transactions Report',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, headings: array<int, string>, rows: Collection<int, array<int, mixed>>}
     */
    public function build(string $type, array $filters = []): array
    {
        return match ($type) {
            'scholar_information' => $this->scholarInformation($filters),
            'certificate_requests' => $this->certificateRequests($filters),
            'or_verification' => $this->officialReceipts($filters),
            'masterlists' => $this->masterlists($filters),
            'renewal_evaluations' => $this->renewalEvaluations($filters),
            'requirement_submissions' => $this->requirementSubmissions($filters),
            'fund_sources' => $this->fundSources($filters),
            'approved_rejected' => $this->approvedRejected($filters),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function scholarInformation(array $filters): array
    {
        $rows = Student::query()
            ->with('scholarshipApplications')
            ->when($filters['student'] ?? null, function ($query, string $student): void {
                $query->where('student_id_number', 'like', "%{$student}%")
                    ->orWhere('first_name', 'like', "%{$student}%")
                    ->orWhere('last_name', 'like', "%{$student}%");
            })
            ->latest()
            ->get()
            ->map(fn (Student $student): array => [
                $student->student_id_number,
                $student->fullName(),
                $student->course,
                $student->year_level,
                $student->campus,
                str($student->status)->headline(),
                $student->scholarshipApplications->pluck('scholarship_program')->implode('; '),
            ]);

        return $this->report('Scholar Information Report', ['Student ID', 'Name', 'Course', 'Year', 'Campus', 'Status', 'Scholarship History'], $rows);
    }

    private function certificateRequests(array $filters): array
    {
        $rows = CertificateRequest::query()
            ->with('student')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['student'] ?? null, fn ($query, string $student) => $query->whereHas('student', fn ($query) => $query->where('student_id_number', 'like', "%{$student}%")->orWhere('first_name', 'like', "%{$student}%")->orWhere('last_name', 'like', "%{$student}%")))
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->get()
            ->map(fn (CertificateRequest $request): array => [
                $request->id,
                $request->student->student_id_number,
                $request->student->fullName(),
                $request->purpose,
                $request->status->label(),
                optional($request->created_at)->format('Y-m-d'),
            ]);

        return $this->report('Certificate Request Report', ['Request ID', 'Student ID', 'Student', 'Purpose', 'Status', 'Submitted'], $rows);
    }

    private function officialReceipts(array $filters): array
    {
        $rows = CertificateRequest::query()
            ->with('student')
            ->whereIn('status', [CertificateRequestStatus::Verified, CertificateRequestStatus::Rejected, CertificateRequestStatus::Approved])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (CertificateRequest $request): array => [
                $request->id,
                $request->student->fullName(),
                $request->official_receipt,
                $request->status->label(),
                $request->remarks,
                optional($request->verified_at)->format('Y-m-d H:i'),
            ]);

        return $this->report('Official Receipt Verification Report', ['Request ID', 'Student', 'Receipt File', 'Status', 'Remarks', 'Verified At'], $rows);
    }

    private function masterlists(array $filters): array
    {
        $rows = ScholarshipMasterlist::query()
            ->with('agency')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (ScholarshipMasterlist $masterlist): array => [
                $masterlist->id,
                $masterlist->agency->agency_name,
                $masterlist->file_name,
                str($masterlist->status)->headline(),
                $masterlist->total_records,
                $masterlist->enrolled_count,
                $masterlist->unenrolled_count,
                $masterlist->duplicate_count,
                $masterlist->invalid_count,
            ]);

        return $this->report('Scholarship Masterlist Report', ['ID', 'Agency', 'File', 'Status', 'Total', 'Enrolled', 'Unenrolled', 'Duplicate', 'Invalid'], $rows);
    }

    private function renewalEvaluations(array $filters): array
    {
        $rows = ScholarshipApplication::query()
            ->with(['student', 'evaluator'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (ScholarshipApplication $application): array => [
                $application->id,
                $application->student->fullName(),
                $application->scholarship_program,
                $application->fund_source,
                $application->status->label(),
                $application->evaluator?->name,
                optional($application->evaluated_at)->format('Y-m-d H:i'),
                $application->remarks,
            ]);

        return $this->report('Continuing Scholarship Evaluation Report', ['ID', 'Student', 'Program', 'Fund Source', 'Status', 'Evaluator', 'Evaluated At', 'Remarks'], $rows);
    }

    private function requirementSubmissions(array $filters): array
    {
        $rows = ScholarshipRequirement::query()
            ->with('application.student')
            ->latest()
            ->get()
            ->map(fn (ScholarshipRequirement $requirement): array => [
                $requirement->application->id,
                $requirement->application->student->fullName(),
                $requirement->application->scholarship_program,
                $requirement->requirement_name,
                str($requirement->status)->headline(),
                $requirement->file_path ? 'Uploaded' : 'Missing',
                optional($requirement->created_at)->format('Y-m-d'),
            ]);

        return $this->report('Student Requirement Submission Report', ['Application ID', 'Student', 'Program', 'Requirement', 'Status', 'Document', 'Submitted'], $rows);
    }

    private function fundSources(array $filters): array
    {
        $programRows = ScholarshipProgram::query()
            ->when($filters['fund_source'] ?? null, fn ($query, string $fundSource) => $query->where('fund_source', $fundSource))
            ->get()
            ->map(fn (ScholarshipProgram $program): array => [
                $program->agency_name,
                $program->fund_source,
                $program->name,
                str($program->status)->headline(),
                'Configured Program',
            ]);

        $recordRows = MasterlistRecord::query()
            ->with('masterlist.agency')
            ->when($filters['fund_source'] ?? null, fn ($query, string $fundSource) => $query->where('fund_source', $fundSource))
            ->selectRaw('min(id) as id, fund_source, scholarship_program, masterlist_id, count(*) as scholar_count')
            ->groupBy('fund_source', 'scholarship_program', 'masterlist_id')
            ->get()
            ->map(fn (MasterlistRecord $record): array => [
                $record->masterlist?->agency?->agency_name,
                $record->fund_source,
                $record->scholarship_program,
                $record->scholar_count.' scholar records',
                'Released/Uploaded Records',
            ]);

        return $this->report('Scholarship Fund Source Report', ['Agency', 'Fund Source', 'Program', 'Status/Count', 'Source'], $programRows->concat($recordRows)->values());
    }

    private function approvedRejected(array $filters): array
    {
        $certificateRows = CertificateRequest::query()
            ->with('student')
            ->whereIn('status', [CertificateRequestStatus::Approved, CertificateRequestStatus::Rejected])
            ->get()
            ->map(fn (CertificateRequest $request): array => [
                'Certificate Request',
                $request->id,
                $request->student->fullName(),
                $request->status->label(),
                $request->remarks,
                optional($request->updated_at)->format('Y-m-d'),
            ]);

        $renewalRows = ScholarshipApplication::query()
            ->with('student')
            ->whereIn('status', [ScholarshipApplicationStatus::Approved, ScholarshipApplicationStatus::Rejected])
            ->get()
            ->map(fn (ScholarshipApplication $application): array => [
                'Renewal Evaluation',
                $application->id,
                $application->student->fullName(),
                $application->status->label(),
                $application->remarks,
                optional($application->updated_at)->format('Y-m-d'),
            ]);

        $masterlistRows = MasterlistRecord::query()
            ->whereIn('chairman_status', ['approved', 'rejected'])
            ->get()
            ->map(fn (MasterlistRecord $record): array => [
                'Masterlist Record',
                $record->id,
                $record->student_name,
                str($record->chairman_status)->headline(),
                $record->remarks,
                optional($record->updated_at)->format('Y-m-d'),
            ]);

        return $this->report('Approved and Rejected Transactions Report', ['Type', 'ID', 'Subject', 'Result', 'Remarks', 'Date'], $certificateRows->concat($renewalRows)->concat($masterlistRows)->values());
    }

    /**
     * @param  array<int, string>  $headings
     * @param  Collection<int, array<int, mixed>>  $rows
     * @return array{title: string, headings: array<int, string>, rows: Collection<int, array<int, mixed>>}
     */
    private function report(string $title, array $headings, Collection $rows): array
    {
        return compact('title', 'headings', 'rows');
    }
}
