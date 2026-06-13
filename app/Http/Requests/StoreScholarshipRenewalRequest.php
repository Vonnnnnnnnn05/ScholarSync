<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreScholarshipRenewalRequest extends FormRequest
{
    public const REQUIRED_DOCUMENTS = [
        'grades_file' => 'Latest Grades',
        'enrollment_file' => 'Certificate of Enrollment',
        'valid_id_file' => 'Valid School ID',
    ];

    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Student) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scholarship_program' => ['required', 'string', 'max:255'],
            'fund_source' => ['nullable', 'string', 'max:255'],
            'grades_file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024)],
            'enrollment_file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024)],
            'valid_id_file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024)],
        ];
    }
}
