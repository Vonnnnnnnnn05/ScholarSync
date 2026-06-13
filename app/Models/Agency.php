<?php

namespace App\Models;

use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_name',
        'contact_person',
        'email',
        'contact_number',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterlists(): HasMany
    {
        return $this->hasMany(ScholarshipMasterlist::class);
    }
}
