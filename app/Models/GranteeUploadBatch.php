<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GranteeUploadBatch extends Model
{
    protected $fillable = [
        'uploaded_by',
        'campus_id',
        'agency_name',
        'scholarship_name',
        'academic_year',
        'semester',
        'original_file_name',
        'uploaded_filenames',
        'stored_file_path',
        'file_type',
        'raw_headers',
        'total_rows',
        'green_count',
        'amber_count',
        'red_count',
        'pending_count',
        'status',
        'verified_at',
        'committed_at',
        'committed_by',
        'delivered_to_coordinator_at',
        'delivered_to_coordinator_by',
        'coordinator_endorsed_at',
        'coordinator_endorsed_by',
        'coordinator_notes',
        'sent_to_registrar_at',
        'sent_to_registrar_by',
        'coordinator_to_registrar_notes',
        'registrar_verified_at',
        'registrar_verified_by',
        'registrar_notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_filenames' => 'array',
            'raw_headers' => 'array',
            'verified_at' => 'datetime',
            'committed_at' => 'datetime',
            'delivered_to_coordinator_at' => 'datetime',
            'coordinator_endorsed_at' => 'datetime',
            'sent_to_registrar_at' => 'datetime',
            'registrar_verified_at' => 'datetime',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function deliveredToCoordinatorBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_to_coordinator_by');
    }

    public function sentToRegistrarBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to_registrar_by');
    }

    public function registrarVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_verified_by');
    }

    public function coordinatorEndorsedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_endorsed_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    public function stagedRecords(): HasMany
    {
        return $this->hasMany(StagedGranteeRecord::class, 'batch_id');
    }

    /** Recalculate verification status counters from staged records. */
    public function refreshCounters(): void
    {
        $counts = $this->stagedRecords()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN verification_status = 'GREEN_VERIFIED' THEN 1 ELSE 0 END) as green,
                SUM(CASE WHEN verification_status = 'AMBER_CONFLICT' THEN 1 ELSE 0 END) as amber,
                SUM(CASE WHEN verification_status = 'RED_UNMATCHED' THEN 1 ELSE 0 END) as red,
                SUM(CASE WHEN verification_status = 'PENDING' THEN 1 ELSE 0 END) as pending
            ")
            ->first();

        $this->update([
            'total_rows' => $counts->total ?? 0,
            'green_count' => $counts->green ?? 0,
            'amber_count' => $counts->amber ?? 0,
            'red_count' => $counts->red ?? 0,
            'pending_count' => $counts->pending ?? 0,
        ]);
    }
}
