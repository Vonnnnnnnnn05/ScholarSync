<?php

namespace App\Models;

use Database\Factories\MasterlistRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterlistRecord extends Model
{
    /** @use HasFactory<MasterlistRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'masterlist_id',
        'campus_id',
        'matched_student_id',
        'student_id_number',
        'student_name',
        'scholarship_program',
        'fund_source',
        'verification_status',
        'eligibility_status',
        'coordinator_status',
        'chairman_status',
        'remarks',
    ];

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
}
