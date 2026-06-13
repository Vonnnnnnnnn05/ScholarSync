<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChairmanMasterlistRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::ScholarshipChairman) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chairman_status' => [
                'required',
                'string',
                Rule::in(['approved', 'rejected']),
            ],
            'remarks' => [
                Rule::requiredIf(fn (): bool => $this->input('chairman_status') === 'rejected'),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
