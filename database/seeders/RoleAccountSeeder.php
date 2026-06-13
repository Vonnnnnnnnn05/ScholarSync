<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAccountSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, email: string, role: UserRole}>
     */
    private array $accounts = [
        'student' => [
            'name' => 'ScholarSync Student',
            'email' => 'student@scholarsync.test',
            'role' => UserRole::Student,
        ],
        'administrator' => [
            'name' => 'ScholarSync Administrator',
            'email' => 'admin@scholarsync.test',
            'role' => UserRole::Administrator,
        ],
        'agency' => [
            'name' => 'ScholarSync Scholarship Agency',
            'email' => 'agency@scholarsync.test',
            'role' => UserRole::ScholarshipAgency,
        ],
        'coordinator' => [
            'name' => 'ScholarSync Coordinator',
            'email' => 'coordinator@scholarsync.test',
            'role' => UserRole::Coordinator,
        ],
        'chairman' => [
            'name' => 'ScholarSync Scholarship Chairman',
            'email' => 'chairman@scholarsync.test',
            'role' => UserRole::ScholarshipChairman,
        ],
    ];

    public function run(): void
    {
        foreach ($this->accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                ],
            );

            $this->createRelatedProfile($user);
        }
    }

    private function createRelatedProfile(User $user): void
    {
        if ($user->hasRole(UserRole::Student)) {
            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_id_number' => 'SKSU-2026-0001',
                    'first_name' => 'ScholarSync',
                    'middle_name' => null,
                    'last_name' => 'Student',
                    'course' => 'BS Information Technology',
                    'year_level' => '4th Year',
                    'campus' => 'ACCESS Campus',
                    'contact_number' => '09123456789',
                    'status' => 'active',
                ],
            );
        }

        if ($user->hasRole(UserRole::ScholarshipAgency)) {
            Agency::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'agency_name' => 'ScholarSync Partner Agency',
                    'contact_person' => $user->name,
                    'email' => $user->email,
                    'contact_number' => '09987654321',
                    'status' => 'active',
                ],
            );
        }
    }
}
