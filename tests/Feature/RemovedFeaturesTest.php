<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

test('agency authentication and continuing scholarship features are absent', function () {
    expect(Route::has('dashboard.scholarship-agency'))->toBeFalse()
        ->and(Route::has('agency.masterlists.index'))->toBeFalse()
        ->and(Route::has('agency.policies.index'))->toBeFalse()
        ->and(Route::has('student.scholarship-renewals.index'))->toBeFalse()
        ->and(Route::has('evaluator.scholarship-renewals.index'))->toBeFalse()
        ->and(Schema::hasTable('scholarship_applications'))->toBeFalse()
        ->and(Schema::hasTable('scholarship_requirements'))->toBeFalse();
});

test('legacy agency users are deleted without deleting agency records', function () {
    $userId = User::query()->insertGetId([
        'name' => 'Legacy Agency', 'email' => 'legacy-agency@example.test',
        'password' => bcrypt('password'), 'role' => 'student', 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $agency = Agency::factory()->create(['user_id' => $userId]);

    $agency->update(['user_id' => null]);
    User::query()->whereKey($userId)->delete();

    expect($agency->refresh()->exists)->toBeTrue()->and($agency->user_id)->toBeNull();
});
