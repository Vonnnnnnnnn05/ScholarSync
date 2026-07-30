<?php

namespace App\Http\Controllers;

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->role->dashboardRouteName());
    }

    public function student(): View
    {
        return $this->show(UserRole::Student);
    }

    public function administrator(): View
    {
        return $this->show(UserRole::Administrator);
    }

    public function scholarshipAgency(): View
    {
        return $this->show(UserRole::ScholarshipAgency);
    }

    public function coordinator(): View
    {
        return $this->show(UserRole::Coordinator);
    }

    public function scholarshipChairman(): View
    {
        return $this->show(UserRole::ScholarshipChairman);
    }

    public function registrar(): View
    {
        return $this->show(UserRole::Registrar);
    }

    private function show(UserRole $role): View
    {
        $studentProfile = $role === UserRole::Student
            ? request()->user()->student
            : null;

        return view('dashboards.show', [
            'role' => $role,
            'title' => $role->label().' Dashboard',
            'summary' => $this->summaryFor($role),
            'items' => $this->itemsFor($role),
            'roleFunctions' => $this->roleFunctionsFor($role),
            'studentProfile' => $studentProfile,
            'adminDashboard' => $role === UserRole::Administrator ? $this->adminDashboard() : null,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function itemsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Student => [
                'Track scholarship applications',
                'Request certificates',
                'View scholarship eligibility and agency policies',
                'Submit renewal requirements',
            ],
            UserRole::Administrator => [
                'Manage user accounts and roles',
                'Evaluate scholarship renewals',
                'Monitor reports and activity',
            ],
            UserRole::ScholarshipAgency => [
                'Upload scholarship masterlists',
                'Publish scholarship rules and eligibility policies',
                'Track duplicate records for review',
            ],
            UserRole::Coordinator => [
                'Review enrolled scholar records',
                'Validate unenrolled scholar records',
                'Evaluate continuing scholarship renewals',
            ],
            UserRole::ScholarshipChairman => [
                'Review coordinator-submitted masterlists',
                'Approve or reject scholar records',
                'Release final scholar records to agencies',
            ],
            UserRole::Registrar => [
                'Maintain official enrolled-student records',
                'Support microservice enrollment matching',
                'Provide reliable enrollment data for validation',
            ],
        };
    }

    private function summaryFor(UserRole $role): string
    {
        return match ($role) {
            UserRole::Student => 'Your student workspace for scholarships, requests, and updates.',
            UserRole::Administrator => 'Administrative overview for managing ScholarSync access and records.',
            UserRole::ScholarshipAgency => 'Agency workspace for scholarship program coordination.',
            UserRole::Coordinator => 'Coordinator dashboard for reviewing and preparing scholarship records.',
            UserRole::ScholarshipChairman => 'Chairman dashboard for final scholarship review and approvals.',
            UserRole::Registrar => 'Registrar dashboard for maintaining official enrollment records used in validation.',
        };
    }

    /**
     * @return array<int, array{title: string, details: array<int, string>}>
     */
    private function roleFunctionsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Student => [
                [
                    'title' => 'Certificate Requests',
                    'details' => [
                        'Request a Certificate of No Scholarship.',
                        'Enter purpose and required request details.',
                        'Upload Official Receipt files.',
                        'Track Pending, Verified, Rejected, and Approved request statuses.',
                        'View remarks and download approved generated certificates.',
                    ],
                ],
                [
                    'title' => 'Scholarship Discovery',
                    'details' => [
                        'View scholarship details posted by agencies.',
                        'Review eligibility requirements, documentary requirements, and deadlines.',
                        'Download published agency policies and guidelines.',
                    ],
                ],
                [
                    'title' => 'Continuing Scholarship Renewal',
                    'details' => [
                        'Upload scholarship renewal requirements.',
                        'Track Submitted, Under Evaluation, Approved, Rejected, and Need Revision statuses.',
                        'Resubmit requirements when revisions are requested.',
                    ],
                ],
            ],
            UserRole::Administrator => [
                [
                    'title' => 'Verification and Certificates',
                    'details' => [
                        'View submitted Official Receipt uploads.',
                        'Verify valid OR files or reject invalid uploads with remarks.',
                        'Approve certificate requests and view generated certificate records.',
                    ],
                ],
                [
                    'title' => 'Monitoring and Reports',
                    'details' => [
                        'View central monitoring dashboard charts and summaries.',
                        'Monitor student profiles, scholar records, transactions, fund sources, and audit trail.',
                        'Generate and export reports as PDF, Excel, or CSV.',
                    ],
                ],
                [
                    'title' => 'Evaluation',
                    'details' => [
                        'Review continuing scholarship renewal applications.',
                        'Add evaluation remarks and approval, rejection, or revision decisions.',
                    ],
                ],
            ],
            UserRole::ScholarshipAgency => [
                [
                    'title' => 'Masterlist Management',
                    'details' => [
                        'Upload scholar masterlist CSV files.',
                        'Preview CSV rows before final submission.',
                        'Review missing, invalid, and duplicate fields.',
                        'Submit masterlists for system verification.',
                    ],
                ],
                [
                    'title' => 'Scholarship Policies',
                    'details' => [
                        'Publish scholarship rules, policies, and guidelines.',
                        'Set eligibility and documentary requirements visible to students.',
                        'Upload downloadable policy files.',
                    ],
                ],
                [
                    'title' => 'Released Results',
                    'details' => [
                        'View uploaded masterlist history.',
                        'Track validation results.',
                        'View final released scholar records after chairman approval.',
                    ],
                ],
            ],
            UserRole::Coordinator => [
                [
                    'title' => 'Masterlist Validation',
                    'details' => [
                        'View pending verified masterlists for validation.',
                        'Review enrolled, unenrolled, duplicate, invalid, qualified, and unqualified records.',
                        'Add remarks and save coordinator validation status.',
                        'Submit fully reviewed masterlists to the Scholarship Chairman.',
                    ],
                ],
                [
                    'title' => 'Renewal Evaluation',
                    'details' => [
                        'Evaluate continuing scholarship renewal applications.',
                        'Add evaluation remarks and decisions.',
                    ],
                ],
            ],
            UserRole::ScholarshipChairman => [
                [
                    'title' => 'Final Masterlist Approval',
                    'details' => [
                        'View masterlists submitted by coordinators.',
                        'Review enrolled, unenrolled, duplicate, invalid, qualified, and unqualified records.',
                        'Approve valid scholar records.',
                        'Reject invalid records with required remarks.',
                    ],
                ],
                [
                    'title' => 'Final Release',
                    'details' => [
                        'Record final approval decisions and approval date.',
                        'Release final scholar records to scholarship agencies.',
                    ],
                ],
            ],
            UserRole::Registrar => [
                [
                    'title' => 'Enrollment Records',
                    'details' => [
                        'Add official enrolled-student records.',
                        'Maintain enrollment status, course, campus, academic year, and semester.',
                        'Provide the trusted enrollment source used by microservice verification.',
                    ],
                ],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDashboard(): array
    {
        $certificateStatuses = collect(CertificateRequestStatus::cases())
            ->map(fn (CertificateRequestStatus $status): array => [
                'label' => $status->label(),
                'value' => CertificateRequest::query()->where('status', $status)->count(),
            ])
            ->all();

        $renewalStatuses = collect(ScholarshipApplicationStatus::cases())
            ->map(fn (ScholarshipApplicationStatus $status): array => [
                'label' => $status->label(),
                'value' => ScholarshipApplication::query()->where('status', $status)->count(),
            ])
            ->all();

        $verificationStatuses = collect([
            'enrolled' => 'Enrolled',
            'unenrolled' => 'Unenrolled',
            'duplicate' => 'Duplicate',
            'invalid' => 'Invalid',
            'pending' => 'Pending',
        ])->map(fn (string $label, string $status): array => [
            'label' => $label,
            'value' => MasterlistRecord::query()->where('verification_status', $status)->count(),
        ])->values()->all();

        $roleDistribution = collect(UserRole::cases())
            ->map(fn (UserRole $role): array => [
                'label' => $role->label(),
                'value' => User::query()->where('role', $role)->count(),
            ])
            ->all();

        $monthlyCertificateRequests = collect(range(5, 0))
            ->map(function (int $monthsAgo): array {
                $month = CarbonImmutable::now()->subMonths($monthsAgo)->startOfMonth();

                return [
                    'label' => $month->format('M'),
                    'value' => CertificateRequest::query()
                        ->whereBetween('created_at', [$month, $month->endOfMonth()])
                        ->count(),
                ];
            })
            ->all();

        return [
            'metrics' => [
                ['label' => 'Total Scholars', 'value' => Student::query()->count(), 'accent' => 'emerald'],
                ['label' => 'Registrar Enrolled', 'value' => RegistrarStudent::query()->where('enrollment_status', 'enrolled')->count(), 'accent' => 'emerald'],
                ['label' => 'Certificate Requests', 'value' => CertificateRequest::query()->count(), 'accent' => 'blue'],
                ['label' => 'Uploaded Masterlists', 'value' => ScholarshipMasterlist::query()->count(), 'accent' => 'amber'],
                ['label' => 'Renewal Applications', 'value' => ScholarshipApplication::query()->count(), 'accent' => 'slate'],
            ],
            'certificateStatuses' => $certificateStatuses,
            'renewalStatuses' => $renewalStatuses,
            'verificationStatuses' => $verificationStatuses,
            'roleDistribution' => $roleDistribution,
            'monthlyCertificateRequests' => $monthlyCertificateRequests,
        ];
    }
}
