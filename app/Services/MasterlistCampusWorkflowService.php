<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\MasterlistCampusBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterlistCampusWorkflowService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function submitToRegistrar(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Coordinator);
        $this->requireStatus($batch, 'with_coordinator');

        DB::transaction(function () use ($batch, $user): void {
            $batch->update(['status' => 'with_registrar', 'submitted_to_registrar_by' => $user->id, 'submitted_to_registrar_at' => now()]);
            $batch->masterlist()->update(['status' => 'campus_verification']);
        });
        $this->audit->record('masterlist_batch_submitted_to_registrar', $batch, ['campus_id' => $batch->campus_id]);
    }

    public function returnToCoordinator(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Registrar);
        $this->requireStatus($batch, 'with_registrar');
        if ($batch->records()->where('verification_status', 'pending')->exists()) {
            throw ValidationException::withMessages(['return' => 'Verify every beneficiary before returning this batch.']);
        }
        $batch->update(['status' => 'returned_to_coordinator', 'returned_by' => $user->id, 'returned_at' => now()]);
        $this->audit->record('masterlist_batch_returned_to_coordinator', $batch, ['campus_id' => $batch->campus_id]);
    }

    public function submitToChairman(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Coordinator);
        $this->requireStatus($batch, 'returned_to_coordinator');
        DB::transaction(function () use ($batch, $user): void {
            $batch->update(['status' => 'submitted_to_chairman', 'submitted_to_chairman_by' => $user->id, 'submitted_to_chairman_at' => now()]);
            $masterlist = $batch->masterlist;
            if (! $masterlist->campusBatches()->where('status', '!=', 'submitted_to_chairman')->exists()) {
                $masterlist->update(['status' => 'ready_for_consolidation']);
            }
        });
        $this->audit->record('masterlist_batch_submitted_to_chairman', $batch, ['campus_id' => $batch->campus_id]);
    }

    private function authorizeCampusActor(MasterlistCampusBatch $batch, User $user, UserRole $role): void
    {
        abort_unless($user->hasRole($role) && $user->campus_id && $user->campus_id === $batch->campus_id, 403);
    }

    private function requireStatus(MasterlistCampusBatch $batch, string $status): void
    {
        if ($batch->status !== $status) {
            throw ValidationException::withMessages(['workflow' => 'This campus batch cannot advance from its current status.']);
        }
    }
}
