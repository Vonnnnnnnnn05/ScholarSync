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

        if ($user->hasRole(UserRole::ScholarshipChairman) && ! $user->hasSignature()) {
            $path = 'signatures/user_'.$user->id.'_seeded.png';
            $width = 320;
            $height = 120;
            $img = imagecreatetruecolor($width, $height);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);
            imagealphablending($img, true);

            $ink = imagecolorallocate($img, 15, 23, 42);
            imagesetthickness($img, 3);

            imageline($img, 35, 95, 60, 25, $ink);
            imageline($img, 60, 25, 85, 95, $ink);
            imageline($img, 45, 65, 80, 60, $ink);
            imagearc($img, 105, 45, 30, 28, 90, 270, $ink);
            imagearc($img, 110, 72, 32, 30, 270, 90, $ink);
            imageline($img, 140, 20, 140, 95, $ink);
            imagearc($img, 155, 38, 35, 32, 270, 90, $ink);
            imagearc($img, 160, 75, 45, 40, 270, 90, $ink);
            imagearc($img, 200, 70, 50, 30, 0, 360, $ink);
            imageline($img, 20, 102, 290, 100, $ink);

            ob_start();
            imagepng($img);
            $pngData = ob_get_clean();
            imagedestroy($img);

            \Illuminate\Support\Facades\Storage::disk('local')->put($path, $pngData);
            $user->forceFill([
                'signature_path' => $path,
                'signature_updated_at' => now(),
            ])->save();
        }
    }
}
