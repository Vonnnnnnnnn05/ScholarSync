<?php

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('student personal details can be updated from profile page', function () {
    $user = User::factory()->role(UserRole::Student)->create();
    Student::factory()->for($user)->create([
        'student_id_number' => 'SKSU-2026-9401',
        'first_name' => 'Old',
        'last_name' => 'Name',
        'course' => 'Bachelor of Science in Nursing',
        'year_level' => '1st Year',
        'section' => 'NUR-1A',
        'campus' => 'ACCESS Campus (Main)',
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Student Personal Details')
        ->assertSee('ACCESS Campus (Main)');

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.student-details.update'), [
            'student_id_number' => 'SKSU-2026-9402',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => '4th Year',
            'section' => 'IT-4A',
            'campus' => 'Isulan Campus',
            'contact_number' => '09123456789',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Maria Santos Reyes')
        ->and($user->student->student_id_number)->toBe('SKSU-2026-9402')
        ->and($user->student->course)->toBe('Bachelor of Science in Information Technology')
        ->and($user->student->campus)->toBe('Isulan Campus')
        ->and($user->student->section)->toBe('IT-4A');
});

test('non student users cannot update student personal details', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->patch(route('profile.student-details.update'), [
            'student_id_number' => 'SKSU-2026-9501',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => '4th Year',
            'section' => 'IT-4A',
            'campus' => 'Isulan Campus',
        ])
        ->assertForbidden();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
