<?php

namespace App\Models;

use App\Enums\ScholarshipApplicationStatus;
use Database\Factories\ScholarshipApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipApplication extends Model
{
    /** @use HasFactory<ScholarshipApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'scholarship_program',
        'fund_source',
        'status',
        'remarks',
        'evaluated_by',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScholarshipApplicationStatus::class,
            'evaluated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ScholarshipRequirement::class, 'application_id');
    }

    public function canBeRevisedByStudent(): bool
    {
        return $this->status === ScholarshipApplicationStatus::NeedRevision;
    }
}
