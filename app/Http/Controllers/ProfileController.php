<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'student' => $request->user()->student,
            'academicOptions' => $this->academicOptions(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the student's personal and academic details.
     */
    public function updateStudentDetails(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRole::Student), 403);

        $academicOptions = $this->academicOptions();
        $campuses = collect($academicOptions['campuses'] ?? [])->pluck('name')->all();
        $courses = collect($academicOptions['campuses'] ?? [])
            ->flatMap(fn (array $campus): array => $campus['programs'] ?? [])
            ->merge($academicOptions['graduate_programs'] ?? [])
            ->unique()
            ->values()
            ->all();
        $yearLevels = $academicOptions['dropdowns']['year_levels'] ?? [];
        $student = $request->user()->student;

        $validated = $request->validateWithBag('studentDetails', [
            'student_id_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'student_id_number')->ignore($student?->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255', Rule::in($courses)],
            'year_level' => ['required', 'string', 'max:255', Rule::in($yearLevels)],
            'section' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255', Rule::in($campuses)],
            'contact_number' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $student, $validated): void {
            $student = Student::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    ...$validated,
                    'status' => $student?->status ?? 'active',
                ],
            );

            $request->user()->forceFill([
                'name' => $student->fullName(),
            ])->save();
        });

        return Redirect::route('profile.edit')->with('status', 'student-details-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return array<string, mixed>
     */
    private function academicOptions(): array
    {
        $path = public_path('data/sksu-academic-options.json');

        if (! file_exists($path)) {
            return [
                'campuses' => [],
                'graduate_programs' => [],
                'dropdowns' => ['year_levels' => []],
            ];
        }

        return json_decode((string) file_get_contents($path), true) ?: [
            'campuses' => [],
            'graduate_programs' => [],
            'dropdowns' => ['year_levels' => []],
        ];
    }
}
