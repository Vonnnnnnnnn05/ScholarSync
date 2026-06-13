<?php

namespace App\Models;

use App\Enums\CertificateRequestStatus;
use Database\Factories\CertificateRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CertificateRequest extends Model
{
    /** @use HasFactory<CertificateRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'purpose',
        'official_receipt',
        'status',
        'remarks',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CertificateRequestStatus::class,
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function scopeOwnedByUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->whereHas('student', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public function isCertificateAvailable(): bool
    {
        return $this->status === CertificateRequestStatus::Approved;
    }
}
