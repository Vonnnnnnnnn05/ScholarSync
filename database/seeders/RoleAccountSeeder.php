<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Campus;
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
        'chairman' => [
            'name' => 'ScholarSync Scholarship Chairman',
            'email' => 'chairman@scholarsync.test',
            'role' => UserRole::ScholarshipChairman,
        ],
    ];

    public function run(): void
    {
        $this->call(CampusSeeder::class);
        User::query()
            ->whereIn('email', ['coordinator@scholarsync.test', 'registrar@scholarsync.test'])
            ->update(['status' => 'inactive']);
        foreach ($this->accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role' => $account['role'],
                    'campus_id' => null,
                    'email_verified_at' => now(),
                ],
            );

            $this->createRelatedProfile($user);
        }

        foreach (Campus::query()->where('is_active', true)->orderBy('id')->get() as $campus) {
            foreach ([UserRole::Coordinator, UserRole::Registrar] as $role) {
                User::updateOrCreate(
                    ['email' => $role->value.'.'.$campus->code.'@scholarsync.test'],
                    [
                        'name' => $campus->name.' '.$role->label(),
                        'password' => Hash::make('password'),
                        'role' => $role,
                        'campus_id' => $campus->id,
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ],
                );
            }
        }

        Agency::firstOrCreate(
            ['agency_name' => 'ScholarSync Partner Agency'],
            ['contact_person' => 'ScholarSync Contact', 'email' => 'agency@scholarsync.test', 'status' => 'active'],
        );
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

    }
}
