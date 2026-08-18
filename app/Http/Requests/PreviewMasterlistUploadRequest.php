<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class PreviewMasterlistUploadRequest extends FormRequest
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
            'masterlist' => [
                'required',
                File::types(['csv', 'txt'])->max(5 * 1024),
            ],
        ];
    }
}
