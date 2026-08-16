<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('administrator can view user management', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $account = User::factory()->create(['name' => "Gladys D'Amore"]);

    $this->actingAs($administrator)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('User Management')
        ->assertSee('Create User Account')
        ->assertSee('data-open-create-account', false)
        ->assertSee('x-show="createOpen"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-label="Edit '.e($account->name).'"', false)
        ->assertSee('aria-label="Delete '.e($account->name).'"', false)
        ->assertSee('fa-pen-to-square', false)
        ->assertSee('fa-trash-can', false)
        ->assertSee(route('admin.users.update', $account), false)
        ->assertSee(route('admin.users.destroy', $account), false)
        ->assertDontSee('Scholarship Agency');
});

test('create account validation errors mark the creation modal to reopen', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'role', 'password']);

    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-create-modal-open="true"', false);
});

test('administrator can edit an account without changing its password', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $campus = Campus::factory()->create(['is_active' => true]);
    $account = User::factory()->role(UserRole::Student)->create();
    $password = $account->password;

    $this->actingAs($administrator)
        ->patch(route('admin.users.update', $account), [
            'name' => 'Updated Coordinator',
            'email' => 'updated.coordinator@example.test',
            'role' => UserRole::Coordinator->value,
            'campus_id' => $campus->id,
            'status' => 'active',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('admin.users.index'));

    $account->refresh();

    expect($account->name)->toBe('Updated Coordinator')
        ->and($account->email)->toBe('updated.coordinator@example.test')
        ->and($account->role)->toBe(UserRole::Coordinator)
        ->and($account->campus_id)->toBe($campus->id)
        ->and($account->password)->toBe($password)
        ->and(AuditLog::where('action', 'user_updated')->where('auditable_id', $account->id)->exists())->toBeTrue();
});

test('administrator can reset a password while editing an account', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $account = User::factory()->create();

    $this->actingAs($administrator)->patch(route('admin.users.update', $account), [
        'name' => $account->name,
        'email' => $account->email,
        'role' => $account->role->value,
        'status' => 'active',
        'password' => 'NewSecurePassword123!',
        'password_confirmation' => 'NewSecurePassword123!',
    ])->assertRedirect(route('admin.users.index'));

    expect(Hash::check('NewSecurePassword123!', $account->fresh()->password))->toBeTrue();
});

test('administrator can deactivate an account but not their own account', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $account = User::factory()->create(['status' => 'active']);

    $this->actingAs($administrator)
        ->delete(route('admin.users.destroy', $account))
        ->assertRedirect(route('admin.users.index'));

    expect($account->fresh()->status)->toBe('inactive')
        ->and(AuditLog::where('action', 'user_deactivated')->where('auditable_id', $account->id)->exists())->toBeTrue();

    $this->actingAs($administrator)
        ->delete(route('admin.users.destroy', $administrator))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('account');

    expect($administrator->fresh()->status)->toBe('active');
});

test('non administrators cannot edit or deactivate accounts', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $account = User::factory()->create();

    $this->actingAs($coordinator)
        ->patch(route('admin.users.update', $account), [])
        ->assertForbidden();

    $this->actingAs($coordinator)
        ->delete(route('admin.users.destroy', $account))
        ->assertForbidden();
});

test('administrator can create accounts for each role', function (UserRole $role) {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $campus = Campus::factory()->create();

    $this->actingAs($administrator)
        ->post(route('admin.users.store'), [
            'name' => "Test {$role->label()}",
            'email' => "{$role->value}@example.test",
            'role' => $role->value,
            'campus_id' => $role->requiresCampus() ? $campus->id : null,
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

test('campus roles require an assigned campus', function (UserRole $role) {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)->post(route('admin.users.store'), [
        'name' => $role->label(), 'email' => $role->value.'-missing@example.test',
        'role' => $role->value, 'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ])->assertSessionHasErrors('campus_id');
})->with([UserRole::Coordinator, UserRole::Registrar]);

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
