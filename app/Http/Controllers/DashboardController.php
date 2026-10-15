<?php

namespace App\Http\Controllers;

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\RegistrarStudent;
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
        $user = request()->user();
        $studentProfile = $role === UserRole::Student
            ? $user->student
            : null;
        $assignedCampus = $role === UserRole::Registrar
            ? $user->loadMissing('campus')->campus
            : null;

        return view('dashboards.show', [
            'role' => $role,
            'title' => $role->label().' Dashboard',
            'summary' => $this->summaryFor($role),
            'items' => $this->itemsFor($role),
            'roleFunctions' => $this->roleFunctionsFor($role),
            'studentProfile' => $studentProfile,
            'assignedCampus' => $assignedCampus,
            'adminDashboard' => in_array($role, [UserRole::Administrator, UserRole::ScholarshipChairman], true) ? $this->adminDashboard() : null,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function itemsFor(UserRole $role): array
    {
        return match ($role) {
            UserRole::Student => [
                'View available scholarships and official application links',
                'Request certificates',
                'View scholarship eligibility and agency policies',
            ],
            UserRole::Administrator => [
                'Manage user accounts and roles',
                'Publish scholarship opportunities',
                'Monitor reports and activity',
            ],
            UserRole::Coordinator => [
                'Receive beneficiary records for the assigned campus',
                'Route beneficiary batches to the Campus Registrar',
                'Review and return Registrar results to the Chairman',
            ],
            UserRole::ScholarshipChairman => [
                'Certificate Management: Verify student ORs and approve Certificates of Non-Grantee (Module A)',
                'Scholarships (Module B): Post external scholarship opportunities, qualifications, and requirements',
                'Verify Grantees: Ingest agency masterlists and cross-check against enrollment database',
                'Scholars: Access final directory of verified scholarship grantees filterable by campus and program',
            ],
            UserRole::Registrar => [
                'Maintain official enrolled-student records',
                'Verify assigned-campus beneficiary records',
                'Return Verified or Not Verified results to the Coordinator',
            ],
        };
    }

    private function summaryFor(UserRole $role): string
    {
        return match ($role) {
            UserRole::Student => 'Your student workspace for scholarships, requests, and updates.',
            UserRole::Administrator => 'Administrative overview for managing ScholarSync access and records.',
            UserRole::Coordinator => 'Coordinator dashboard for routing campus beneficiary records and reviewing Registrar results.',
            UserRole::ScholarshipChairman => 'Scholarship Chairman workspace for certificate management, scholarship postings, grantee verification, and scholar monitoring.',
            UserRole::Registrar => 'Registrar dashboard for official campus beneficiary verification and enrollment records.',
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
                        'View scholarship details published by administrators.',
                        'Review eligibility requirements, documentary requirements, and deadlines.',
                        'Use official external scholarship application links.',
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
                        'Monitor student profiles, scholar records, transactions, and audit trail.',
                        'Generate and export reports as PDF, Excel, or CSV.',
                    ],
                ],
                [
                    'title' => 'Scholarship Opportunities',
                    'details' => [
                        'Enter agency and program details when publishing opportunities.',
                        'Publish qualifications, requirements, deadlines, and official application links.',
                    ],
                ],
            ],
            UserRole::Coordinator => [
                [
                    'title' => 'Campus Beneficiary Routing',
                    'details' => [
                        'Receive only beneficiary batches assigned to your campus.',
                        'Submit batches to the Campus Registrar without changing verification results.',
                        'Review returned results and submit them to the Scholarship Chairman.',
                    ],
                ],
            ],
            UserRole::ScholarshipChairman => [
                [
                    'title' => 'Certificate Management (Module A)',
                    'details' => [
                        'Review student Official Receipts and approve Certificate of Non-Grantee requests.',
                        'Issue tamper-evident, serial-numbered Certificates of Non-Grantee.',
                        'Restricted from generating certificates for students with active scholarship grants.',
                    ],
                ],
                [
                    'title' => 'Scholarship Opportunities (Module B)',
                    'details' => [
                        'Post external scholarship opportunities with qualifications, deadlines, and official links.',
                        'Manage active, published, and draft scholarship postings for student discovery.',
                    ],
                ],
                [
                    'title' => 'Grantee Ingestion & Verification',
                    'details' => [
                        'Upload agency PDF/Excel files with coordinate-aware multi-category auto-classification (TES-UniFAST, TDP, CMSP).',
                        'Cross-check records against pre-loaded school enrollment database.',
                        'Resolve amber conflict rows and commit verified grantees to ScholarSync.',
                    ],
                ],
                [
                    'title' => 'Scholars Directory',
                    'details' => [
                        'Search and monitor verified scholarship awardees across all seven SKSU campuses.',
                        'Filter grantees dynamically by campus and scholarship program.',
                    ],
                ],
            ],
            UserRole::Registrar => [
                [
                    'title' => 'Official Beneficiary Verification',
                    'details' => [
                        'Maintain official enrollment records for your campus.',
                        'Compare assigned beneficiaries with official student information.',
                        'Mark each beneficiary Verified or Not Verified and return the results.',
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
            ],
            'certificateStatuses' => $certificateStatuses,
            'verificationStatuses' => $verificationStatuses,
            'roleDistribution' => $roleDistribution,
            'monthlyCertificateRequests' => $monthlyCertificateRequests,
        ];
    }
}
