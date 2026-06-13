<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:administrator'])
        ->get('/__test/admin-only', fn () => response('admin ok'))
        ->name('__test.admin-only');

    Route::middleware(['web', 'auth', 'role:coordinator,scholarship_chairman'])
        ->get('/__test/reviewer-only', fn () => response('reviewer ok'))
        ->name('__test.reviewer-only');
});

test('users table has a role column', function () {
    expect(Schema::hasColumn('users', 'role'))->toBeTrue();
});

test('new users default to the student role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::Student)
        ->and($user->hasRole(UserRole::Student))->toBeTrue();
});

test('role middleware allows matching role', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get('/__test/admin-only')
        ->assertOk()
        ->assertSee('admin ok');
});

test('role middleware allows any configured role', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $this->actingAs($chairman)
        ->get('/__test/reviewer-only')
        ->assertOk()
        ->assertSee('reviewer ok');
});

test('role middleware rejects non matching role', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get('/__test/admin-only')
        ->assertForbidden();
});

test('role middleware redirects guests to login', function () {
    $this->get('/__test/admin-only')
        ->assertRedirect(route('login'));
});
