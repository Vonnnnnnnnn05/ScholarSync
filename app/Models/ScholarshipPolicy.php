<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'scholarship_program_id',
        'title',
        'description',
        'eligibility_requirements',
        'documentary_requirements',
        'deadline',
        'application_link',
        'file_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }
}
