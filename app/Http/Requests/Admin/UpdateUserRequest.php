<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Administrator) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => str((string) $this->input('email'))->trim()->lower()->toString(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $account = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'campus_id' => [
                Rule::requiredIf(fn (): bool => UserRole::tryFrom((string) $this->input('role'))?->requiresCampus() === true),
                'nullable',
                'integer',
                Rule::exists('campuses', 'id')->where('is_active', true),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $role = UserRole::tryFrom((string) $this->input('role'));
            $campusId = $this->integer('campus_id');
            $account = $this->route('user');

            if ($this->input('status') === 'active' && $role?->requiresCampus() && $campusId && User::query()
                ->whereKeyNot($account?->getKey())->where('role', $role->value)
                ->where('campus_id', $campusId)->where('status', 'active')->exists()) {
                $validator->errors()->add('campus_id', 'This campus already has an active '.$role->label().'.');
            }
        });
    }
}
