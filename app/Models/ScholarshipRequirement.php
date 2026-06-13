<?php

namespace App\Models;

use Database\Factories\ScholarshipRequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipRequirement extends Model
{
    /** @use HasFactory<ScholarshipRequirementFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'requirement_name',
        'file_path',
        'status',
        'remarks',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'application_id');
    }
}
