<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterlistRegistrarResolution extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['previous_results' => 'array', 'new_results' => 'array'];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MasterlistRecord::class, 'masterlist_record_id');
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_id');
    }
}
