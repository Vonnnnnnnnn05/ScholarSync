<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Jobs\VerifyMasterlistChunk;
use App\Models\MasterlistCampusBatch;
use App\Models\MasterlistVerificationRun;
use App\Models\RegistrarStudent;
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
        $chunkSize = (int) config('services.masterlist_verifier.chunk_size', 500);

        $run = DB::transaction(function () use ($batch, $user, $chunkSize): MasterlistVerificationRun {
            $total = $batch->records()->count();
            if ($total === 0) {
                throw ValidationException::withMessages(['workflow' => 'An empty campus batch cannot be submitted.']);
            }
            $batch->update(['status' => 'verification_queued', 'submitted_to_registrar_by' => $user->id, 'submitted_to_registrar_at' => now()]);
            $batch->masterlist()->update(['status' => 'campus_verification']);

            return $batch->verificationRuns()->create([
                'status' => 'queued', 'total_records' => $total, 'total_chunks' => (int) ceil($total / $chunkSize),
            ]);
        });
        $batch->records()->oldest('id')->pluck('id')->chunk($chunkSize)->each(
            fn ($ids) => VerifyMasterlistChunk::dispatch($run->id, $ids->values()->all())->afterCommit()
        );
        $this->audit->record('masterlist_batch_submitted_to_registrar', $batch, ['campus_id' => $batch->campus_id]);
    }

    public function returnToCoordinator(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Registrar);
        $this->requireStatus($batch, 'awaiting_registrar_review');
        if ($batch->records()->where(function ($query): void {
            $query->where('final_enrollment_status', 'needs_review')
                ->orWhere('final_cor_status', 'needs_review')
                ->orWhere('final_qualification_status', 'needs_review');
        })->exists()) {
            throw ValidationException::withMessages(['return' => 'Resolve every Needs Review result before returning this batch.']);
        }
        $batch->update(['status' => 'returned_to_coordinator', 'returned_by' => $user->id, 'returned_at' => now()]);
        $this->audit->record('masterlist_batch_returned_to_coordinator', $batch, ['campus_id' => $batch->campus_id]);
    }

    public function retryVerification(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Coordinator);
        $this->requireStatus($batch, 'verification_failed');
        $previous = $batch->verificationRuns()->latest()->firstOrFail();
        $completedIds = $previous->recordVerifications()->pluck('masterlist_record_id');
        $remainingIds = $batch->records()->whereNotIn('id', $completedIds)->oldest('id')->pluck('id');
        if ($remainingIds->isEmpty()) {
            throw ValidationException::withMessages(['workflow' => 'No failed verification records remain to retry.']);
        }
        $chunkSize = (int) config('services.masterlist_verifier.chunk_size', 500);
        $run = $batch->verificationRuns()->create([
            'status' => 'queued', 'total_records' => $remainingIds->count(), 'total_chunks' => (int) ceil($remainingIds->count() / $chunkSize),
        ]);
        $batch->update(['status' => 'verification_queued']);
        $remainingIds->chunk($chunkSize)->each(fn ($ids) => VerifyMasterlistChunk::dispatch($run->id, $ids->values()->all())->afterCommit());
        $this->audit->record('masterlist_batch_verification_retried', $batch, ['campus_id' => $batch->campus_id, 'records' => $remainingIds->count()]);
    }

    public function reverifyExceptions(MasterlistCampusBatch $batch, User $user): void
    {
        $this->authorizeCampusActor($batch, $user, UserRole::Registrar);

        if (! in_array($batch->status, ['awaiting_registrar_review', 'verification_failed'], true)) {
            throw ValidationException::withMessages(['reverify' => 'Automatic verification cannot be restarted while this batch is processing or has already advanced.']);
        }

        if (! RegistrarStudent::query()->where('campus_id', $batch->campus_id)->exists()) {
            throw ValidationException::withMessages(['reverify' => 'Upload enrollment records for your campus before running automatic verification again.']);
        }

        $recordIds = $batch->records()
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query->where('match_status', '!=', 'matched')
                    ->orWhere('final_enrollment_status', '!=', 'enrolled')
                    ->orWhere('final_cor_status', '!=', 'cor_printed')
                    ->orWhere('final_qualification_status', '!=', 'qualified');
            })
            ->oldest('id')
            ->pluck('id');

        if ($recordIds->isEmpty()) {
            throw ValidationException::withMessages(['reverify' => 'No unresolved exception records remain to verify again.']);
        }

        $chunkSize = (int) config('services.masterlist_verifier.chunk_size', 500);
        $run = DB::transaction(function () use ($batch, $recordIds, $chunkSize): MasterlistVerificationRun {
            $batch->update(['status' => 'verification_queued']);

            return $batch->verificationRuns()->create([
                'status' => 'queued',
                'total_records' => $recordIds->count(),
                'total_chunks' => (int) ceil($recordIds->count() / $chunkSize),
            ]);
        });

        $recordIds->chunk($chunkSize)->each(
            fn ($ids) => VerifyMasterlistChunk::dispatch($run->id, $ids->values()->all())->afterCommit()
        );

        $this->audit->record('masterlist_batch_exceptions_reverification_queued', $batch, [
            'campus_id' => $batch->campus_id,
            'records' => $recordIds->count(),
        ]);
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
