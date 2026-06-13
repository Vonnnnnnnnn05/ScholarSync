<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

test('admin user seeder creates an administrator account', function () {
    $this->seed(AdminUserSeeder::class);

    $administrator = User::where('email', 'admin@scholarsync.test')->firstOrFail();

    expect($administrator->name)->toBe('ScholarSync Administrator')
        ->and($administrator->role)->toBe(UserRole::Administrator)
        ->and($administrator->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $administrator->password))->toBeTrue();
});
