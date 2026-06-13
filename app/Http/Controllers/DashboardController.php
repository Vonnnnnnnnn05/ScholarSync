<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
}
