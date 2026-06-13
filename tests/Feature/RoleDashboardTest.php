<?php

use App\Enums\UserRole;
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
        ->assertSee($role->label());
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
        ->assertSee('Evaluation Status')
        ->assertSee('User Role Distribution');
});
