<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Administrator = 'administrator';
    case Coordinator = 'coordinator';
    case ScholarshipChairman = 'scholarship_chairman';
    case Registrar = 'registrar';

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
            self::Student => 'Student',
            self::Administrator => 'Administrator',
            self::Coordinator => 'Campus Scholarship Coordinator',
            self::ScholarshipChairman => 'Scholarship Chairman',
            self::Registrar => 'Campus Registrar',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Student => 'dashboard.student',
            self::Administrator => 'dashboard.administrator',
            self::Coordinator => 'dashboard.coordinator',
            self::ScholarshipChairman => 'dashboard.scholarship-chairman',
            self::Registrar => 'dashboard.registrar',
        };
    }

    public function requiresCampus(): bool
    {
        return in_array($this, [self::Coordinator, self::Registrar], true);
    }
}
