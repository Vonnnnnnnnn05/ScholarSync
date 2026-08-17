<?php

use App\Enums\UserRole;
use App\Models\User;

test('only coordinators can access the campus routing queue', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('coordinator.masterlists.index'))
        ->assertForbidden();
});

test('obsolete coordinator verification endpoints are unavailable', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->patch('/coordinator/masterlists/1/records/1', ['verification_status' => 'verified'])
        ->assertNotFound();
});
