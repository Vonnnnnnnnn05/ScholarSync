<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Campus;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAccountSeeder;
use Illuminate\Support\Facades\Hash;

test('role account seeder creates only approved login roles with campus assignments', function () {
    $this->seed(RoleAccountSeeder::class);

    $accounts = [
        'student@scholarsync.test' => UserRole::Student,
        'admin@scholarsync.test' => UserRole::Administrator,
        'coordinator@scholarsync.test' => UserRole::Coordinator,
        'chairman@scholarsync.test' => UserRole::ScholarshipChairman,
        'registrar@scholarsync.test' => UserRole::Registrar,
    ];

    foreach ($accounts as $email => $role) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    }

    expect(User::where('email', 'agency@scholarsync.test')->exists())->toBeFalse()
        ->and(Campus::query()->count())->toBe(7)
        ->and(User::where('email', 'coordinator@scholarsync.test')->firstOrFail()->campus_id)->not->toBeNull()
        ->and(User::where('email', 'registrar@scholarsync.test')->firstOrFail()->campus_id)->not->toBeNull();

    expect(Student::whereRelation('user', 'email', 'student@scholarsync.test')->exists())->toBeTrue()
        ->and(Agency::query()->exists())->toBeTrue();
});
