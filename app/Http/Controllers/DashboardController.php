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
                'Upload and distribute beneficiary master lists by campus',
                'Monitor verification progress across all seven campuses',
                'Export and forward consolidated verified lists',
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
            UserRole::ScholarshipChairman => 'Chairman dashboard for campus progress, consolidation, and external export.',
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
                    'title' => 'Campus Progress and Consolidation',
                    'details' => [
                        'Upload agency-provided masterlists and distribute records by campus.',
                        'Monitor independent verification progress across all seven campuses.',
                        'Consolidate only records verified by Campus Registrars.',
                        'Export the final verified list for the external Scholarship Agency.',
                    ],
                ],
                [
                    'title' => 'Final Release',
                    'details' => [
                        'Download the verified-only beneficiary CSV.',
                        'Record when the final list is forwarded to an external agency.',
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
