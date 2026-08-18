<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterlistRecordVerification extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['original_data' => 'array', 'matched_data' => 'array', 'verified_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(MasterlistVerificationRun::class, 'masterlist_verification_run_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MasterlistRecord::class, 'masterlist_record_id');
    }
}
