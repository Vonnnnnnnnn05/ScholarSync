<?php

namespace App\Enums;

enum CertificateRequestStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Approved = 'approved';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Approved => 'Approved',
        };
    }

    public function step(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::Verified => 2,
            self::Rejected => 2,
            self::Approved => 3,
        };
    }
}
