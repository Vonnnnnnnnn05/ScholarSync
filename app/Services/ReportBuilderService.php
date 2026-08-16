<?php

namespace App\Services;

use App\Enums\CertificateRequestStatus;
use App\Models\Agency;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\ScholarshipPolicy;
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
            'scholarship_agencies' => 'Scholarship Agency Report',
            'approved_rejected' => 'Approved and Rejected Transactions Report',
            'enrollment_verification' => 'Enrollment Verification Report',
            'agency_submissions' => 'Agency Submission Report',
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
            'scholarship_agencies' => $this->scholarshipAgencies($filters),
            'approved_rejected' => $this->approvedRejected($filters),
            'enrollment_verification' => $this->enrollmentVerification($filters),
            'agency_submissions' => $this->agencySubmissions($filters),
            default => abort(404),
        };
    }

    private function scholarInformation(array $filters): array
    {
        $rows = Student::query()
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
            ]);

        return $this->report('Scholar Information Report', ['Student ID', 'Name', 'Course', 'Year', 'Campus', 'Status'], $rows);
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
                $masterlist->qualified_count,
                $masterlist->unqualified_count,
            ]);

        return $this->report('Scholarship Masterlist Report', ['ID', 'Agency', 'File', 'Status', 'Total', 'Enrolled', 'Unenrolled', 'Duplicate', 'Invalid', 'Qualified', 'Unqualified'], $rows);
    }

    private function scholarshipAgencies(array $filters): array
    {
        $rows = Agency::query()
            ->withCount(['policies', 'masterlists'])
            ->when($filters['agency'] ?? null, fn ($query, string $agency) => $query->where('agency_name', 'like', "%{$agency}%"))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('agency_name')
            ->get()
            ->map(fn (Agency $agency): array => [
                $agency->agency_name,
                $agency->contact_person,
                $agency->email,
                $agency->contact_number,
                str($agency->status)->headline(),
                $agency->policies_count,
                $agency->masterlists_count,
            ]);

        return $this->report('Scholarship Agency Report', ['Agency', 'Contact Person', 'Email', 'Contact Number', 'Status', 'Opportunities', 'Masterlists'], $rows);
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

        return $this->report('Approved and Rejected Transactions Report', ['Type', 'ID', 'Subject', 'Result', 'Remarks', 'Date'], $certificateRows->concat($masterlistRows)->values());
    }

    private function enrollmentVerification(array $filters): array
    {
        $rows = MasterlistRecord::query()
            ->with('masterlist.agency')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('verification_status', $status)->orWhere('eligibility_status', $status))
            ->when($filters['student'] ?? null, fn ($query, string $student) => $query->where('student_id_number', 'like', "%{$student}%")->orWhere('student_name', 'like', "%{$student}%"))
            ->when($filters['agency'] ?? null, fn ($query, string $agency) => $query->whereHas('masterlist.agency', fn ($query) => $query->where('agency_name', 'like', "%{$agency}%")))
            ->latest()
            ->get()
            ->map(fn (MasterlistRecord $record): array => [
                $record->masterlist?->agency?->agency_name,
                $record->student_id_number,
                $record->student_name,
                $record->scholarship_program,
                str($record->verification_status)->headline(),
                str($record->eligibility_status)->headline(),
                $record->remarks,
            ]);

        return $this->report('Enrollment Verification Report', ['Agency', 'Student ID', 'Student', 'Program', 'Enrollment Status', 'Eligibility', 'Remarks'], $rows);
    }

    private function agencySubmissions(array $filters): array
    {
        $masterlistRows = ScholarshipMasterlist::query()
            ->with('agency')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->get()
            ->map(fn (ScholarshipMasterlist $masterlist): array => [
                $masterlist->agency->agency_name,
                'Masterlist',
                $masterlist->file_name,
                str($masterlist->status)->headline(),
                $masterlist->total_records.' records',
                optional($masterlist->created_at)->format('Y-m-d'),
            ]);

        $policyRows = ScholarshipPolicy::query()
            ->with(['agency', 'program'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->get()
            ->map(fn (ScholarshipPolicy $policy): array => [
                $policy->agency->agency_name,
                'Policy / Eligibility',
                $policy->title,
                str($policy->status)->headline(),
                $policy->program?->name ?? 'General policy',
                optional($policy->created_at)->format('Y-m-d'),
            ]);

        return $this->report('Agency Submission Report', ['Agency', 'Submission Type', 'Title/File', 'Status', 'Details', 'Submitted'], $masterlistRows->concat($policyRows)->values());
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
