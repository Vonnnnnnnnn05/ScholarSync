<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterlistVerificationRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function campusBatch(): BelongsTo
    {
        return $this->belongsTo(MasterlistCampusBatch::class, 'masterlist_campus_batch_id');
    }

    public function recordVerifications(): HasMany
    {
        return $this->hasMany(MasterlistRecordVerification::class);
    }
}
