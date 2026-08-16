<?php

use App\Enums\UserRole;
use App\Models\Campus;
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

test('system exposes exactly the five approved user roles', function () {
    expect(array_column(UserRole::cases(), 'value'))->toBe([
        'student',
        'administrator',
        'coordinator',
        'scholarship_chairman',
        'registrar',
    ])->and(UserRole::Coordinator->label())->toBe('Campus Scholarship Coordinator')
        ->and(UserRole::Registrar->label())->toBe('Campus Registrar');
});

test('campus roles require a campus while university roles do not', function () {
    expect(UserRole::Coordinator->requiresCampus())->toBeTrue()
        ->and(UserRole::Registrar->requiresCampus())->toBeTrue()
        ->and(UserRole::Student->requiresCampus())->toBeFalse()
        ->and(UserRole::Administrator->requiresCampus())->toBeFalse()
        ->and(UserRole::ScholarshipChairman->requiresCampus())->toBeFalse();
});

test('users can be assigned to a campus', function () {
    $campus = Campus::factory()->create();
    $user = User::factory()->role(UserRole::Coordinator)->create(['campus_id' => $campus->id]);

    expect($user->campus->is($campus))->toBeTrue();
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
