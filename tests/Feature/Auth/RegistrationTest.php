<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('ACCESS Campus (Main)')
        ->assertSee('Isulan Campus')
        ->assertSee('4th Year');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'student_id_number' => 'SKSU-2026-9201',
        'first_name' => 'Test',
        'middle_name' => 'Middle',
        'last_name' => 'User',
        'course' => 'Bachelor of Science in Information Technology',
        'year_level' => '4th Year',
        'section' => 'IT-4A',
        'campus' => 'ACCESS Campus (Main)',
        'contact_number' => '09123456789',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->password)
        ->not->toBe('password')
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->student)->not->toBeNull()
        ->and($user->student->student_id_number)->toBe('SKSU-2026-9201')
        ->and($user->student->campus)->toBe('ACCESS Campus (Main)')
        ->and($user->student->section)->toBe('IT-4A');
});
