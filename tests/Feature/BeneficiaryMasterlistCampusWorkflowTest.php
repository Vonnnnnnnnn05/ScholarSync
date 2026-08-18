<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Campus;
use App\Models\MasterlistCampusBatch;
use App\Models\MasterlistRecord;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipMasterlist;
use App\Models\User;
use App\Services\MasterlistCsvService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('beneficiary upload requires a valid campus for every student', function () {
    Storage::fake('local');
    $campus = Campus::factory()->create(['code' => 'isulan', 'name' => 'Isulan Campus']);
    Storage::disk('local')->put('masterlists/tmp/valid.csv', "student_name,campus\nAna Cruz,Isulan Campus\n");
    Storage::disk('local')->put('masterlists/tmp/invalid.csv', "student_name,campus\nJuan Cruz,Unknown Campus\n");

    $valid = app(MasterlistCsvService::class)->preview('masterlists/tmp/valid.csv');
    $invalid = app(MasterlistCsvService::class)->preview('masterlists/tmp/invalid.csv');

    expect(MasterlistCsvService::REQUIRED_COLUMNS)->toBe(['student_name', 'campus'])
        ->and($valid['rows'][0]['campus_id'])->toBe($campus->id)
        ->and($valid['rows'][0]['is_invalid'])->toBeFalse()
        ->and($invalid['rows'][0]['is_invalid'])->toBeTrue()
        ->and($invalid['rows'][0]['errors'])->toContain('Campus must match one of the seven active SKSU campuses.');
});

test('campus batch schema supports independent campus progress', function () {
    expect(Schema::hasTable('masterlist_campus_batches'))->toBeTrue()
        ->and(Schema::hasColumns('masterlist_records', ['verified_by', 'verified_at']))->toBeTrue();
});

test('upload import distributes pending records into represented campus batches', function () {
    Storage::fake('local');
    $firstCampus = Campus::factory()->create(['code' => 'isulan', 'name' => 'Isulan Campus']);
    $secondCampus = Campus::factory()->create(['code' => 'tacurong', 'name' => 'Tacurong Campus']);
    $agency = Agency::factory()->create();
    Storage::disk('local')->put('masterlists/tmp/beneficiaries.csv', "student_name,campus\nAna Cruz,Isulan Campus\nJuan Cruz,Tacurong Campus\n");

    $masterlist = app(MasterlistCsvService::class)->import($agency, 'masterlists/tmp/beneficiaries.csv', 'beneficiaries.csv');

    expect($masterlist->status)->toBe('distributed')
        ->and($masterlist->campusBatches()->count())->toBe(2)
        ->and($masterlist->records()->where('campus_id', $firstCampus->id)->where('verification_status', 'pending')->count())->toBe(1)
        ->and($masterlist->records()->where('campus_id', $secondCampus->id)->where('verification_status', 'pending')->count())->toBe(1);
});

function campusWorkflowFixture(): array
{
    $campus = Campus::factory()->create();
    $coordinator = User::factory()->role(UserRole::Coordinator)->create(['campus_id' => $campus->id]);
    $registrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $campus->id]);
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create(['status' => 'distributed']);
    $batch = MasterlistCampusBatch::create([
        'masterlist_id' => $masterlist->id,
        'campus_id' => $campus->id,
        'status' => 'with_coordinator',
    ]);
    $record = MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'campus_id' => $campus->id,
        'student_name' => 'Ana Cruz',
        'verification_status' => 'pending',
    ]);
    RegistrarStudent::create([
        'student_id_number' => 'SKSU-1',
        'student_name' => 'Ana Cruz',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'enrollment_status' => 'enrolled',
    ]);

    return compact('campus', 'coordinator', 'registrar', 'masterlist', 'batch', 'record');
}

test('campus coordinator routes a batch to the registrar without verifying records', function () {
    Queue::fake();
    $fixture = campusWorkflowFixture();

    $this->actingAs($fixture['coordinator'])
        ->post('/coordinator/masterlist-batches/'.$fixture['batch']->id.'/submit-to-registrar')
        ->assertRedirect();

    expect($fixture['batch']->fresh())
        ->status->toBe('verification_queued')
        ->submitted_to_registrar_by->toBe($fixture['coordinator']->id)
        ->and($fixture['record']->fresh()->verification_status)->toBe('pending');
});

test('coordinator queue contains only their campus batches', function () {
    $fixture = campusWorkflowFixture();
    $otherCampus = Campus::factory()->create();
    $otherMasterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create(['status' => 'distributed', 'file_name' => 'other-campus.csv']);
    MasterlistCampusBatch::create(['masterlist_id' => $otherMasterlist->id, 'campus_id' => $otherCampus->id, 'status' => 'with_coordinator']);

    $this->actingAs($fixture['coordinator'])
        ->get(route('coordinator.masterlists.index'))
        ->assertOk()
        ->assertSee($fixture['masterlist']->file_name)
        ->assertDontSee('other-campus.csv');
});

test('registrar verifies a campus batch before coordinator submits it to chairman', function () {
    $fixture = campusWorkflowFixture();
    $fixture['batch']->update(['status' => 'awaiting_registrar_review']);
    $fixture['record']->update([
        'automatic_enrollment_status' => 'needs_review', 'automatic_cor_status' => 'needs_review',
        'automatic_qualification_status' => 'needs_review', 'final_enrollment_status' => 'needs_review',
        'final_cor_status' => 'needs_review', 'final_qualification_status' => 'needs_review',
    ]);

    $this->actingAs($fixture['registrar'])
        ->patch('/registrar/masterlist-batches/'.$fixture['batch']->id.'/records/'.$fixture['record']->id, [
            'final_enrollment_status' => 'enrolled',
            'final_cor_status' => 'cor_printed',
            'final_qualification_status' => 'qualified',
            'reason' => 'Confirmed in official enrollment records.',
        ])
        ->assertRedirect();

    expect($fixture['record']->fresh())
        ->verification_status->toBe('verified')
        ->verified_by->toBe($fixture['registrar']->id);

    $this->actingAs($fixture['registrar'])
        ->post('/registrar/masterlist-batches/'.$fixture['batch']->id.'/return')
        ->assertRedirect();

    $this->actingAs($fixture['coordinator'])
        ->post('/coordinator/masterlist-batches/'.$fixture['batch']->id.'/submit-to-chairman')
        ->assertRedirect();

    expect($fixture['batch']->fresh()->status)->toBe('submitted_to_chairman')
        ->and($fixture['masterlist']->fresh()->status)->toBe('ready_for_consolidation');
});

test('registrars cannot access another campus batch', function () {
    $fixture = campusWorkflowFixture();
    $otherCampus = Campus::factory()->create();
    $otherRegistrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $otherCampus->id]);
    $fixture['batch']->update(['status' => 'awaiting_registrar_review']);

    $this->actingAs($otherRegistrar)
        ->get('/registrar/masterlist-batches/'.$fixture['batch']->id)
        ->assertForbidden();
});

test('chairman exports only registrar verified beneficiaries and releases the final list', function () {
    $fixture = campusWorkflowFixture();
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $fixture['record']->update(['verification_status' => 'verified', 'final_enrollment_status' => 'enrolled', 'final_cor_status' => 'cor_printed', 'final_qualification_status' => 'qualified', 'verified_by' => $fixture['registrar']->id, 'verified_at' => now()]);
    MasterlistRecord::factory()->for($fixture['masterlist'], 'masterlist')->create([
        'campus_id' => $fixture['campus']->id,
        'student_name' => 'Not Eligible Student',
        'verification_status' => 'not_verified',
        'final_enrollment_status' => 'not_enrolled',
        'final_cor_status' => 'no_cor_printed',
        'final_qualification_status' => 'not_qualified',
        'verified_by' => $fixture['registrar']->id,
        'verified_at' => now(),
    ]);
    $fixture['batch']->update(['status' => 'submitted_to_chairman']);
    $fixture['masterlist']->update(['status' => 'ready_for_consolidation']);

    $response = $this->actingAs($chairman)
        ->get('/chairman/masterlists/'.$fixture['masterlist']->id.'/export')
        ->assertOk()
        ->assertDownload();

    expect($response->streamedContent())->toContain('Ana Cruz')->not->toContain('Not Eligible Student');

    $this->actingAs($chairman)
        ->post(route('chairman.masterlists.release', $fixture['masterlist']))
        ->assertRedirect();

    expect($fixture['masterlist']->fresh())
        ->status->toBe('released')
        ->approved_by->toBe($chairman->id);
});

test('chairman cannot release while a represented campus batch is incomplete', function () {
    $fixture = campusWorkflowFixture();
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $this->actingAs($chairman)
        ->post(route('chairman.masterlists.release', $fixture['masterlist']))
        ->assertSessionHasErrors('release');

    expect($fixture['masterlist']->fresh()->status)->toBe('distributed');
});

test('chairman monitors in progress campus masterlists system wide', function () {
    $fixture = campusWorkflowFixture();
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $this->actingAs($chairman)
        ->get(route('chairman.masterlists.index'))
        ->assertOk()
        ->assertSee($fixture['masterlist']->file_name);

    $this->actingAs($chairman)
        ->get(route('chairman.masterlists.show', $fixture['masterlist']))
        ->assertOk()
        ->assertSee($fixture['campus']->name)
        ->assertSee('With Coordinator');
});
