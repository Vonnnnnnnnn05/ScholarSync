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
            'verification_status' => ['required', Rule::in(['verified', 'not_verified'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
