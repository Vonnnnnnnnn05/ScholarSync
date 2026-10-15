<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StagedGranteeRecord extends Model
{
    protected $fillable = [
        'batch_id',
        'row_number',
        'source_pdf_name',
        'raw_payload',
        'first_name',
        'middle_name',
        'last_name',
        'student_name',
        'raw_agency_name',
        'student_id',
        'campus',
        'campus_id',
        'program',
        'scholarship_name',
        'scholarship_program',
        'missing_fields_flag',
        'verification_status',
        'enrollment_status',
        'confidence_score',
        'matched_sis_student_id',
        'matched_student_id',
        'conflict_reason',
        'candidate_matches',
        'remarks',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'missing_fields_flag' => 'array',
            'candidate_matches' => 'array',
            'confidence_score' => 'decimal:4',
            'resolved_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(GranteeUploadBatch::class, 'batch_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function matchedSisStudent(): BelongsTo
    {
        return $this->belongsTo(RegistrarStudent::class, 'matched_sis_student_id');
    }

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'matched_student_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** Get the display name — prefer split names, fallback to student_name. */
    public function displayName(): string
    {
        $parts = collect([$this->first_name, $this->middle_name, $this->last_name])->filter();

        return $parts->isNotEmpty() ? $parts->implode(' ') : ($this->student_name ?? '—');
    }

    /** Check if this record has been resolved (manually or automatically). */
    public function isResolved(): bool
    {
        return in_array($this->verification_status, ['GREEN_VERIFIED', 'RED_UNMATCHED'], true);
    }

    /** Check if this record needs human attention. */
    public function needsResolution(): bool
    {
        return $this->verification_status === 'AMBER_CONFLICT';
    }
}
