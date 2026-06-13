<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\User;

function verifiedMasterlistForCoordinator(): ScholarshipMasterlist
{
    $agency = Agency::factory()->create();
    $masterlist = ScholarshipMasterlist::factory()
        ->for($agency)
        ->create([
            'status' => 'verified',
            'total_records' => 4,
            'enrolled_count' => 1,
            'unenrolled_count' => 1,
            'duplicate_count' => 1,
            'invalid_count' => 1,
            'validated_at' => now(),
        ]);

    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_id_number' => 'SKSU-2026-0001',
        'student_name' => 'Ana Cruz',
        'verification_status' => 'enrolled',
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_id_number' => 'SKSU-2026-0002',
        'student_name' => 'Juan Dela Cruz',
        'verification_status' => 'unenrolled',
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_id_number' => 'SKSU-2026-0003',
        'student_name' => 'Duplicate Scholar',
        'verification_status' => 'duplicate',
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_id_number' => null,
        'student_name' => 'Invalid Scholar',
        'verification_status' => 'invalid',
    ]);

    return $masterlist;
}

test('coordinators can view pending verified masterlists with validation summaries', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $masterlist = verifiedMasterlistForCoordinator();

    $this->actingAs($coordinator)
        ->get(route('coordinator.masterlists.index'))
        ->assertOk()
        ->assertSee('Masterlist Validation')
        ->assertSee($masterlist->agency->agency_name)
        ->assertSee('1 enrolled, 1 unenrolled, 1 duplicate, 1 invalid');
});

test('coordinators can review enrolled and unenrolled scholar records', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $masterlist = verifiedMasterlistForCoordinator();

    $this->actingAs($coordinator)
        ->get(route('coordinator.masterlists.show', [$masterlist, 'status' => 'enrolled']))
        ->assertOk()
        ->assertSee('Ana Cruz')
        ->assertDontSee('Juan Dela Cruz');

    $this->actingAs($coordinator)
        ->get(route('coordinator.masterlists.show', [$masterlist, 'status' => 'unenrolled']))
        ->assertOk()
        ->assertSee('Juan Dela Cruz')
        ->assertDontSee('Ana Cruz');
});

test('coordinators can save record validation status and remarks', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $masterlist = verifiedMasterlistForCoordinator();
    $record = $masterlist->records()->where('verification_status', 'unenrolled')->firstOrFail();

    $this->actingAs($coordinator)
        ->patch(route('coordinator.masterlists.records.update', [$masterlist, $record]), [
            'coordinator_status' => 'for_correction',
            'remarks' => 'Student ID should be checked with the registrar.',
        ])
        ->assertRedirect();

    expect($record->refresh()->coordinator_status)->toBe('for_correction')
        ->and($record->remarks)->toBe('Student ID should be checked with the registrar.')
        ->and($masterlist->refresh()->status)->toBe('coordinator_validation');
});

test('coordinators must review all records before submitting to chairman', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $masterlist = verifiedMasterlistForCoordinator();

    $this->actingAs($coordinator)
        ->post(route('coordinator.masterlists.submit', $masterlist))
        ->assertSessionHasErrors('submit');

    expect($masterlist->refresh()->status)->toBe('verified');
});

test('coordinators can submit fully reviewed masterlists to chairman', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();
    $masterlist = verifiedMasterlistForCoordinator();

    $masterlist->records()->update([
        'coordinator_status' => 'for_chairman_review',
        'remarks' => 'Ready for chairman review.',
    ]);

    $this->actingAs($coordinator)
        ->post(route('coordinator.masterlists.submit', $masterlist))
        ->assertRedirect(route('coordinator.masterlists.show', $masterlist));

    expect($masterlist->refresh()->status)->toBe('submitted_to_chairman')
        ->and($masterlist->validated_by)->toBe($coordinator->id)
        ->and($masterlist->validated_at)->not->toBeNull()
        ->and($masterlist->records()->where('chairman_status', 'pending')->count())->toBe(4);
});

test('non coordinator users cannot access coordinator validation workflow', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('coordinator.masterlists.index'))
        ->assertForbidden();
});
