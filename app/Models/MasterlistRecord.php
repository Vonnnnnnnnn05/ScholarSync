<?php

namespace App\Models;

use Database\Factories\MasterlistRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterlistRecord extends Model
{
    /** @use HasFactory<MasterlistRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'masterlist_id',
        'campus_id',
        'registrar_student_id',
        'matched_student_id',
        'student_id_number',
        'student_name',
        'scholarship_program',
        'fund_source',
        'verification_status',
        'verified_by',
        'verified_at',
        'eligibility_status',
        'coordinator_status',
        'chairman_status',
        'remarks',
        'automatic_enrollment_status', 'automatic_cor_status', 'automatic_qualification_status',
        'final_enrollment_status', 'final_cor_status', 'final_qualification_status',
        'match_status', 'automatic_verified_at', 'automatic_result_message', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'automatic_verified_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function masterlist(): BelongsTo
    {
        return $this->belongsTo(ScholarshipMasterlist::class, 'masterlist_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'matched_student_id');
    }

    public function registrarStudent(): BelongsTo
    {
        return $this->belongsTo(RegistrarStudent::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function verificationSnapshots(): HasMany
    {
        return $this->hasMany(MasterlistRecordVerification::class);
    }

    public function registrarResolutions(): HasMany
    {
        return $this->hasMany(MasterlistRegistrarResolution::class);
    }
}
