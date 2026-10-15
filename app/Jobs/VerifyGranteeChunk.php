<?php

namespace App\Jobs;

use App\Models\GranteeUploadBatch;
use App\Services\GranteeVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class VerifyGranteeChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param GranteeUploadBatch $batch
     * @param array<int, int>|null $recordIds
     */
    public function __construct(
        public GranteeUploadBatch $batch,
        public ?array $recordIds = null
    ) {
        $this->onQueue('grantee-verification');
    }

    public function handle(GranteeVerificationService $service): void
    {
        $service->verifyBatchOrChunk($this->batch, $this->recordIds);
    }

    public function failed(?Throwable $exception): void
    {
        $this->batch->update(['status' => 'failed']);
    }
}
