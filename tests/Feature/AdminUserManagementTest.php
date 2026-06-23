<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrator can view user management', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee('Create User Account')
        ->assertSee('Scholarship Agency');
});

test('administrator can create accounts for each role', function (UserRole $role) {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->post(route('admin.users.store'), [
            'name' => "Test {$role->label()}",
            'email' => "{$role->value}@example.test",
            'role' => $role->value,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])
        ->assertRedirect(route('admin.users.index'));

    $account = User::query()->where('email', "{$role->value}@example.test")->firstOrFail();

    expect($account->role)->toBe($role)
        ->and(Hash::check('SecurePassword123!', $account->password))->toBeTrue()
        ->and($account->email_verified_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'user_created')->where('auditable_id', $account->id)->exists())->toBeTrue();
})->with(UserRole::cases());

test('creating an agency account also creates its agency profile', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->post(route('admin.users.store'), [
            'name' => 'CHED Regional Office',
            'email' => 'ched@example.test',
            'role' => UserRole::ScholarshipAgency->value,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])
        ->assertSessionHasNoErrors();

    expect(Agency::where('agency_name', 'CHED Regional Office')->where('email', 'ched@example.test')->exists())->toBeTrue();
});

test('non administrators cannot manage users', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($coordinator)
        ->post(route('admin.users.store'), [
            'name' => 'Unauthorized Account',
            'email' => 'unauthorized@example.test',
            'role' => UserRole::Administrator->value,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])
        ->assertForbidden();
});

test('user creation validates unique email role and password confirmation', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $existing = User::factory()->create();

    $this->actingAs($administrator)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'name' => 'Invalid Account',
            'email' => $existing->email,
            'role' => 'unknown_role',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'different-password',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors(['email', 'role', 'password']);
});
