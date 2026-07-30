<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'academicOptions' => $this->academicOptions(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $academicOptions = $this->academicOptions();
        $campuses = collect($academicOptions['campuses'] ?? [])->pluck('name')->all();
        $courses = collect($academicOptions['campuses'] ?? [])
            ->flatMap(fn (array $campus): array => $campus['programs'] ?? [])
            ->merge($academicOptions['graduate_programs'] ?? [])
            ->unique()
            ->values()
            ->all();
        $yearLevels = $academicOptions['dropdowns']['year_levels'] ?? [];

        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:255', 'unique:students,student_id_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255', Rule::in($courses)],
            'year_level' => ['required', 'string', 'max:255', Rule::in($yearLevels)],
            'section' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255', Rule::in($campuses)],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $name = collect([
                $validated['first_name'],
                $validated['middle_name'] ?? null,
                $validated['last_name'],
            ])->filter()->implode(' ');

            $user = User::create([
                'name' => $name,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Student::create([
                'user_id' => $user->id,
                'student_id_number' => $validated['student_id_number'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'course' => $validated['course'],
                'year_level' => $validated['year_level'],
                'section' => $validated['section'],
                'campus' => $validated['campus'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => 'active',
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
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
