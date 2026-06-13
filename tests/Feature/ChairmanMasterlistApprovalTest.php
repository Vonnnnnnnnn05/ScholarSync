<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\User;

function submittedMasterlistForChairman(): ScholarshipMasterlist
{
    $agency = Agency::factory()->create();
    $masterlist = ScholarshipMasterlist::factory()
        ->for($agency)
        ->create([
            'status' => 'submitted_to_chairman',
            'total_records' => 4,
            'enrolled_count' => 1,
            'unenrolled_count' => 1,
            'duplicate_count' => 1,
            'invalid_count' => 1,
            'validated_at' => now(),
        ]);

    foreach ([
        ['SKSU-2026-0001', 'Ana Cruz', 'enrolled', 'for_chairman_review'],
        ['SKSU-2026-0002', 'Juan Dela Cruz', 'unenrolled', 'for_chairman_review'],
        ['SKSU-2026-0003', 'Duplicate Scholar', 'duplicate', 'for_chairman_review'],
        [null, 'Invalid Scholar', 'invalid', 'rejected'],
    ] as [$studentId, $name, $verificationStatus, $coordinatorStatus]) {
        MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
            'student_id_number' => $studentId,
            'student_name' => $name,
            'verification_status' => $verificationStatus,
            'coordinator_status' => $coordinatorStatus,
            'chairman_status' => 'pending',
        ]);
    }

    return $masterlist;
}

test('chairman can view submitted masterlists for approval', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();

    $this->actingAs($chairman)
        ->get(route('chairman.masterlists.index'))
        ->assertOk()
        ->assertSee('Masterlist Approvals')
        ->assertSee($masterlist->agency->agency_name)
        ->assertSee('1 enrolled, 1 unenrolled, 1 duplicate, 1 invalid');
});

test('chairman can review enrolled unenrolled duplicate and invalid records', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();

    $this->actingAs($chairman)
        ->get(route('chairman.masterlists.show', [$masterlist, 'status' => 'enrolled']))
        ->assertOk()
        ->assertSee('Ana Cruz')
        ->assertDontSee('Juan Dela Cruz');

    $this->actingAs($chairman)
        ->get(route('chairman.masterlists.show', [$masterlist, 'status' => 'invalid']))
        ->assertOk()
        ->assertSee('Invalid Scholar')
        ->assertDontSee('Ana Cruz');
});

test('chairman can approve valid records', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();
    $record = $masterlist->records()->where('verification_status', 'enrolled')->firstOrFail();

    $this->actingAs($chairman)
        ->patch(route('chairman.masterlists.records.update', [$masterlist, $record]), [
            'chairman_status' => 'approved',
            'remarks' => 'Approved for final scholar list.',
        ])
        ->assertRedirect();

    expect($record->refresh()->chairman_status)->toBe('approved')
        ->and($record->remarks)->toBe('Approved for final scholar list.')
        ->and($masterlist->refresh()->status)->toBe('chairman_review');
});

test('chairman rejection requires remarks', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();
    $record = $masterlist->records()->where('verification_status', 'invalid')->firstOrFail();

    $this->actingAs($chairman)
        ->patch(route('chairman.masterlists.records.update', [$masterlist, $record]), [
            'chairman_status' => 'rejected',
            'remarks' => '',
        ])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($chairman)
        ->patch(route('chairman.masterlists.records.update', [$masterlist, $record]), [
            'chairman_status' => 'rejected',
            'remarks' => 'Invalid record cannot be released.',
        ])
        ->assertRedirect();

    expect($record->refresh()->chairman_status)->toBe('rejected')
        ->and($record->remarks)->toBe('Invalid record cannot be released.');
});

test('chairman must review all records before release', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();

    $this->actingAs($chairman)
        ->post(route('chairman.masterlists.release', $masterlist))
        ->assertSessionHasErrors('release');

    expect($masterlist->refresh()->status)->toBe('submitted_to_chairman');
});

test('chairman can release final scholar records to agency', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $masterlist = submittedMasterlistForChairman();

    $masterlist->records()->whereIn('verification_status', ['enrolled', 'unenrolled'])->update([
        'chairman_status' => 'approved',
    ]);
    $masterlist->records()->whereIn('verification_status', ['duplicate', 'invalid'])->update([
        'chairman_status' => 'rejected',
        'remarks' => 'Not included in final release.',
    ]);

    $this->actingAs($chairman)
        ->post(route('chairman.masterlists.release', $masterlist))
        ->assertRedirect(route('chairman.masterlists.show', $masterlist));

    expect($masterlist->refresh()->status)->toBe('released')
        ->and($masterlist->approved_by)->toBe($chairman->id)
        ->and($masterlist->approved_at)->not->toBeNull();
});

test('agencies can view released final results', function () {
    $agencyUser = User::factory()->role(UserRole::ScholarshipAgency)->create();
    $agency = Agency::factory()->for($agencyUser)->create();
    $masterlist = ScholarshipMasterlist::factory()->for($agency)->create([
        'status' => 'released',
        'total_records' => 1,
        'approved_at' => now(),
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_name' => 'Ana Cruz',
        'verification_status' => 'enrolled',
        'chairman_status' => 'approved',
    ]);

    $this->actingAs($agencyUser)
        ->get(route('agency.masterlists.show', $masterlist))
        ->assertOk()
        ->assertSee('Ana Cruz')
        ->assertSee('Approved');
});

test('non chairman users cannot access chairman approval workflow', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->get(route('chairman.masterlists.index'))
        ->assertForbidden();
});
