<?php

use App\Enums\UserRole;
use App\Models\User;

test('only the scholarship chairman can access masterlist consolidation', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->get(route('chairman.masterlists.index'))
        ->assertForbidden();
});

test('obsolete chairman record approval endpoint is unavailable', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $this->actingAs($chairman)
        ->patch('/chairman/masterlists/1/records/1', ['chairman_status' => 'approved'])
        ->assertNotFound();
});
