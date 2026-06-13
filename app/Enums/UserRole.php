<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Administrator = 'administrator';
    case ScholarshipAgency = 'scholarship_agency';
    case Coordinator = 'coordinator';
    case ScholarshipChairman = 'scholarship_chairman';

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
            self::ScholarshipAgency => 'Scholarship Agency',
            self::Coordinator => 'Coordinator',
            self::ScholarshipChairman => 'Scholarship Chairman',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Student => 'dashboard.student',
            self::Administrator => 'dashboard.administrator',
            self::ScholarshipAgency => 'dashboard.scholarship-agency',
            self::Coordinator => 'dashboard.coordinator',
            self::ScholarshipChairman => 'dashboard.scholarship-chairman',
        };
    }
}
