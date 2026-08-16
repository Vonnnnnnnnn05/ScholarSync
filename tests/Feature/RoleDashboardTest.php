<?php

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

test('dashboard entry redirects each user to their role dashboard', function (UserRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route($role->dashboardRouteName()));
})->with(UserRole::cases());

test('role dashboards can be viewed by matching role users', function (UserRole $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)
        ->get(route($role->dashboardRouteName()))
        ->assertOk()
        ->assertSee($role->label().' Dashboard')
        ->assertSee($role->label())
        ->assertSee('Role Functions')
        ->assertSee('Visible Functions and Fields');
})->with(UserRole::cases());

test('role dashboards reject users with another role', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route(UserRole::Administrator->dashboardRouteName()))
        ->assertForbidden();
});

test('administrator dashboard includes monitoring charts', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route(UserRole::Administrator->dashboardRouteName()))
        ->assertOk()
        ->assertSee('Certificate Request Trend')
        ->assertSee('Verification Mix')
        ->assertSee('Request Status')
        ->assertDontSee('Evaluation Status')
        ->assertSee('User Role Distribution');
});

test('student dashboard displays personal details', function () {
    $studentUser = User::factory()->role(UserRole::Student)->create(['email' => 'student@example.com']);
    Student::factory()->for($studentUser)->create([
        'student_id_number' => 'SKSU-2026-9301',
        'first_name' => 'Maria',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'course' => 'BS Information Technology',
        'year_level' => '4th Year',
        'section' => 'IT-4A',
        'campus' => 'ACCESS Campus',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);

    $this->actingAs($studentUser)
        ->get(route(UserRole::Student->dashboardRouteName()))
        ->assertOk()
        ->assertSee('Personal Details')
        ->assertSee('SKSU-2026-9301')
        ->assertSee('Maria Santos Reyes')
        ->assertSee('ACCESS Campus')
        ->assertSee('IT-4A');
});
