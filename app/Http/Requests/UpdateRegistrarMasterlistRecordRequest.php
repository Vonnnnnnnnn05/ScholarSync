<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrarMasterlistRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Registrar) ?? false;
    }

    public function rules(): array
    {
        return [
            'final_enrollment_status' => ['required', Rule::in(['enrolled', 'not_enrolled'])],
            'final_cor_status' => ['required', Rule::in(['cor_printed', 'no_cor_printed'])],
            'final_qualification_status' => ['required', Rule::in(['qualified', 'not_qualified'])],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
