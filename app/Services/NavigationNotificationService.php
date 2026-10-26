<?php

namespace App\Services;

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\GranteeUploadBatch;
use App\Models\MasterlistCampusBatch;
use App\Models\User;
use App\Models\UserNotification;

class NavigationNotificationService
{
    /**
     * Get pending notification counts for the authenticated user based on role and campus.
     *
     * @return array<string, int>
     */
    public function getCounts(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $counts = [
            'certificate_management' => 0,
            'verify_grantees' => 0,
            'campus_routing' => 0,
            'beneficiary_verification' => 0,
            'student_certificates' => 0,
            'unread_notifications' => 0,
        ];

        // Unread system notifications
        $counts['unread_notifications'] = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Administrator & Scholarship Chairman: Pending / Actionable certificate requests
        if ($user->hasRole(UserRole::Administrator) || $user->hasRole(UserRole::ScholarshipChairman)) {
            $counts['certificate_management'] = CertificateRequest::query()
                ->whereIn('status', [CertificateRequestStatus::Pending, CertificateRequestStatus::Verified])
                ->count();
        }

        // Scholarship Chairman: Grantee masterlist batches pending verification / reconciliation
        if ($user->hasRole(UserRole::ScholarshipChairman)) {
            $counts['verify_grantees'] = GranteeUploadBatch::query()
                ->where('status', '!=', 'committed')
                ->count();
        }

        // Coordinator: Batches for coordinator's campus pending review / endorsement
        if ($user->hasRole(UserRole::Coordinator) && $user->campus_id) {
            $granteeCount = GranteeUploadBatch::query()
                ->where('campus_id', $user->campus_id)
                ->whereNull('coordinator_endorsed_at')
                ->count();

            $masterlistCount = MasterlistCampusBatch::query()
                ->where('campus_id', $user->campus_id)
                ->whereIn('status', ['pending_validation', 'staged'])
                ->count();

            $counts['campus_routing'] = $granteeCount + $masterlistCount;
        }

        // Registrar: Batches sent to campus registrar pending verification
        if ($user->hasRole(UserRole::Registrar) && $user->campus_id) {
            $granteeCount = GranteeUploadBatch::query()
                ->where('campus_id', $user->campus_id)
                ->where('status', 'sent_to_registrar')
                ->count();

            $masterlistCount = MasterlistCampusBatch::query()
                ->where('campus_id', $user->campus_id)
                ->where('status', 'submitted_to_registrar')
                ->count();

            $counts['beneficiary_verification'] = $granteeCount + $masterlistCount;
        }

        // Student: In-progress certificate requests
        if ($user->hasRole(UserRole::Student) && $user->student) {
            $counts['student_certificates'] = $user->student->certificateRequests()
                ->whereIn('status', [CertificateRequestStatus::Pending, CertificateRequestStatus::Verified])
                ->count();
        }

        return $counts;
    }
}
