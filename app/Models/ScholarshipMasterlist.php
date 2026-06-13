<?php

namespace App\Models;

use Database\Factories\ScholarshipMasterlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipMasterlist extends Model
{
    /** @use HasFactory<ScholarshipMasterlistFactory> */
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'file_name',
        'file_path',
        'status',
        'total_records',
        'enrolled_count',
        'unenrolled_count',
        'duplicate_count',
        'invalid_count',
        'validated_by',
        'validated_at',
        'approved_by',
        'approved_at',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'approved_at' => 'datetime',
            'uploaded_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(MasterlistRecord::class, 'masterlist_id');
    }
}
