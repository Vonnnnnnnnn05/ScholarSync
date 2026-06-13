<?php

namespace App\Http\Requests;

use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluateScholarshipRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([UserRole::Administrator, UserRole::Coordinator]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    ScholarshipApplicationStatus::UnderEvaluation->value,
                    ScholarshipApplicationStatus::Approved->value,
                    ScholarshipApplicationStatus::Rejected->value,
                    ScholarshipApplicationStatus::NeedRevision->value,
                ]),
            ],
            'remarks' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('status'), [
                    ScholarshipApplicationStatus::Rejected->value,
                    ScholarshipApplicationStatus::NeedRevision->value,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
