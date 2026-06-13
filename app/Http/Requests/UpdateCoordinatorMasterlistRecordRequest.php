<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoordinatorMasterlistRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Coordinator) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coordinator_status' => [
                'required',
                'string',
                Rule::in(['validated', 'for_correction', 'rejected', 'for_chairman_review']),
            ],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
