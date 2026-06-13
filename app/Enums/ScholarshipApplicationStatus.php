<?php

namespace App\Enums;

enum ScholarshipApplicationStatus: string
{
    case Submitted = 'submitted';
    case UnderEvaluation = 'under_evaluation';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case NeedRevision = 'need_revision';

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
            self::Submitted => 'Submitted',
            self::UnderEvaluation => 'Under Evaluation',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::NeedRevision => 'Need Revision',
        };
    }
}
