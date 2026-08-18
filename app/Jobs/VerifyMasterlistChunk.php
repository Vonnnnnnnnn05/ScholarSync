<?php

namespace App\Jobs;

use App\Models\MasterlistVerificationRun;
use App\Services\MasterlistVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class VerifyMasterlistChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param array<int, int> $recordIds */
    public function __construct(public int $runId, public array $recordIds)
    {
        $this->onQueue('masterlist-verification');
    }

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(MasterlistVerificationService $service): void
    {
        $run = MasterlistVerificationRun::query()->findOrFail($this->runId);
        if ($run->recordVerifications()->whereIn('masterlist_record_id', $this->recordIds)->count() === count($this->recordIds)) {
            return;
        }
        $run->update(['status' => 'processing', 'started_at' => $run->started_at ?? now(), 'attempt_count' => $run->attempt_count + 1]);
        $run->campusBatch()->update(['status' => 'verification_processing']);
        $service->verifyChunk($run, $this->recordIds);

        $run->increment('completed_chunks');
        $run->increment('processed_records', count($this->recordIds));
        $run->refresh();
        if ($run->completed_chunks >= $run->total_chunks) {
            $records = $run->campusBatch->records();
            $run->update([
                'status' => 'completed',
                'qualified_count' => (clone $records)->where('automatic_qualification_status', 'qualified')->count(),
                'not_qualified_count' => (clone $records)->where('automatic_qualification_status', 'not_qualified')->count(),
                'needs_review_count' => (clone $records)->where('automatic_qualification_status', 'needs_review')->count(),
                'completed_at' => now(),
                'failure_message' => null,
            ]);
            $run->campusBatch()->update(['status' => 'awaiting_registrar_review']);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $run = MasterlistVerificationRun::query()->find($this->runId);
        $run?->update(['status' => 'failed', 'failure_message' => $exception?->getMessage(), 'failed_at' => now()]);
        $run?->campusBatch()->update(['status' => 'verification_failed']);
    }
}
