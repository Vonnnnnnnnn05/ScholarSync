<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterlistCampusBatch extends Model
{
    protected $fillable = [
        'masterlist_id', 'campus_id', 'status',
        'submitted_to_registrar_by', 'submitted_to_registrar_at',
        'returned_by', 'returned_at',
        'submitted_to_chairman_by', 'submitted_to_chairman_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_to_registrar_at' => 'datetime',
            'returned_at' => 'datetime',
            'submitted_to_chairman_at' => 'datetime',
        ];
    }

    public function masterlist(): BelongsTo
    {
        return $this->belongsTo(ScholarshipMasterlist::class, 'masterlist_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(MasterlistRecord::class, 'masterlist_id', 'masterlist_id')
            ->where('campus_id', $this->campus_id);
    }

    public function verificationRuns(): HasMany
    {
        return $this->hasMany(MasterlistVerificationRun::class);
    }
}
