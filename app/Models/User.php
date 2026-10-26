<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'campus_id',
        'signature_path',
        'signature_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'signature_updated_at' => 'datetime',
        ];
    }

    public function hasRole(UserRole|string $role): bool
    {
        $role = $role instanceof UserRole ? $role : UserRole::tryFrom($role);

        return $role !== null && $this->role === $role;
    }

    /**
     * @param  iterable<int, UserRole|string>  $roles
     */
    public function hasAnyRole(iterable $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function hasSignature(): bool
    {
        return ! empty($this->signature_path) && Storage::disk('local')->exists($this->signature_path);
    }

    public function signatureDataUri(): ?string
    {
        if (! $this->hasSignature()) {
            return null;
        }

        $content = Storage::disk('local')->get($this->signature_path);
        if ($content === null) {
            return null;
        }

        $mime = str_ends_with(strtolower($this->signature_path), '.jpg') || str_ends_with(strtolower($this->signature_path), '.jpeg')
            ? 'image/jpeg'
            : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
