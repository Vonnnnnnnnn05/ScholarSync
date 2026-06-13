<?php

namespace App\Http\Controllers;

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
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

    private function show(UserRole $role): View
    {
        return view('dashboards.show', [
            'role' => $role,
            'title' => $role->label().' Dashboard',
            'summary' => $this->summaryFor($role),
            'items' => $this->itemsFor($role),
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
                'Submit renewal requirements',
            ],
            UserRole::Administrator => [
                'Manage user accounts and roles',
                'Evaluate scholarship renewals',
                'Monitor reports and activity',
            ],
            UserRole::ScholarshipAgency => [
                'Upload scholarship masterlists',
                'Preview CSV records before import',
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
