<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreCertificateRequestRequest extends FormRequest
{
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
            'student_id_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'course' => ['required', 'string', 'max:120'],
            'year_level' => ['required', 'string', 'max:50'],
            'campus' => ['required', 'string', 'max:120'],
            'contact_number' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'min:10', 'max:1000'],
            'official_receipt' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentData(): array
    {
        return $this->safe()->only([
            'student_id_number',
            'first_name',
            'middle_name',
            'last_name',
            'course',
            'year_level',
            'campus',
            'contact_number',
        ]);
    }
}
