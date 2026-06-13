<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAccountSeeder;
use Illuminate\Support\Facades\Hash;

test('role account seeder creates one login account for each role', function () {
    $this->seed(RoleAccountSeeder::class);

    $accounts = [
        'student@scholarsync.test' => UserRole::Student,
        'admin@scholarsync.test' => UserRole::Administrator,
        'agency@scholarsync.test' => UserRole::ScholarshipAgency,
        'coordinator@scholarsync.test' => UserRole::Coordinator,
        'chairman@scholarsync.test' => UserRole::ScholarshipChairman,
    ];

    foreach ($accounts as $email => $role) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    }

    expect(Student::whereRelation('user', 'email', 'student@scholarsync.test')->exists())->toBeTrue()
        ->and(Agency::whereRelation('user', 'email', 'agency@scholarsync.test')->exists())->toBeTrue();
});
