<?php

use App\Enums\UserRole;
use App\Jobs\VerifyMasterlistChunk;
use App\Models\Agency;
use App\Models\Campus;
use App\Models\MasterlistCampusBatch;
use App\Models\MasterlistRecord;
use App\Models\MasterlistRecordVerification;
use App\Models\MasterlistRegistrarResolution;
use App\Models\MasterlistVerificationRun;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipMasterlist;
use App\Models\User;
use App\Services\MasterlistCampusWorkflowService;
use App\Services\MasterlistCsvService;
use App\Services\MasterlistVerificationService;
use App\Services\VerifiedMasterlistExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('beneficiary masterlist import and export do not require or expose an agency', function () {
    Storage::fake('local');
    $campus = Campus::factory()->create(['name' => 'Isulan Campus', 'code' => 'isulan']);
    Storage::disk('local')->put('masterlists/tmp/plain.csv', "student_name,campus\nAna Cruz,Isulan Campus\n");

    $masterlist = app(MasterlistCsvService::class)->import('masterlists/tmp/plain.csv', 'plain.csv');
    $record = $masterlist->records()->firstOrFail();
    $record->update([
        'final_enrollment_status' => 'enrolled', 'final_cor_status' => 'cor_printed',
        'final_qualification_status' => 'qualified', 'automatic_verified_at' => now(),
    ]);

    expect($masterlist->agency_id)->toBeNull()
        ->and($record->campus_id)->toBe($campus->id);

    $response = app(VerifiedMasterlistExportService::class)->download($masterlist);
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();
    expect($content)->toContain('student_name,campus,enrollment_status')
        ->not->toContain('scholarship_agency');
});

test('chairman masterlist upload asks only for the csv file', function () {
    $chairman = User::factory()->create(['role' => UserRole::ScholarshipChairman]);

    $this->actingAs($chairman)
        ->get(route('chairman.uploads.create'))
        ->assertOk()
        ->assertSee('CSV Masterlist')
        ->assertSee('name="masterlist"', false)
        ->assertDontSee('Agency Details')
        ->assertDontSee('name="agency_id"', false);
});

test('bulk verification schema preserves automatic final snapshot and resolution data', function () {
    expect(Schema::hasTable('masterlist_verification_runs'))->toBeTrue()
        ->and(Schema::hasTable('masterlist_record_verifications'))->toBeTrue()
        ->and(Schema::hasTable('masterlist_registrar_resolutions'))->toBeTrue()
        ->and(Schema::hasColumns('masterlist_records', [
            'automatic_enrollment_status', 'automatic_cor_status', 'automatic_qualification_status',
            'final_enrollment_status', 'final_cor_status', 'final_qualification_status',
            'match_status', 'automatic_verified_at', 'automatic_result_message', 'resolved_by', 'resolved_at',
        ]))->toBeTrue();
});

test('coordinator submission queues campus records in chunks and creates an auditable run', function () {
    Queue::fake();
    $campus = Campus::factory()->create();
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator, 'campus_id' => $campus->id]);
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create(['status' => 'distributed']);
    $batch = MasterlistCampusBatch::create(['masterlist_id' => $masterlist->id, 'campus_id' => $campus->id, 'status' => 'with_coordinator']);
    MasterlistRecord::factory()->count(501)->for($masterlist, 'masterlist')->create(['campus_id' => $campus->id]);

    app(MasterlistCampusWorkflowService::class)->submitToRegistrar($batch, $coordinator);

    expect($batch->refresh()->status)->toBe('verification_queued')
        ->and($batch->verificationRuns()->count())->toBe(1)
        ->and($batch->verificationRuns()->first()->total_chunks)->toBe(2);
    Queue::assertPushed(VerifyMasterlistChunk::class, 2);
    Queue::assertPushed(fn (VerifyMasterlistChunk $job) => count($job->recordIds) <= 500);
});

test('registrar resolves an exception without overwriting automatic results', function () {
    $campus = Campus::factory()->create();
    $registrar = User::factory()->create(['role' => UserRole::Registrar, 'campus_id' => $campus->id]);
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create();
    $batch = MasterlistCampusBatch::create(['masterlist_id' => $masterlist->id, 'campus_id' => $campus->id, 'status' => 'awaiting_registrar_review']);
    $record = MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'campus_id' => $campus->id,
        'automatic_enrollment_status' => 'needs_review', 'automatic_cor_status' => 'needs_review',
        'automatic_qualification_status' => 'needs_review', 'final_enrollment_status' => 'needs_review',
        'final_cor_status' => 'needs_review', 'final_qualification_status' => 'needs_review',
    ]);

    $this->actingAs($registrar)->patch(route('registrar.batches.records.update', [$batch, $record]), [
        'final_enrollment_status' => 'enrolled',
        'final_cor_status' => 'cor_printed',
        'final_qualification_status' => 'qualified',
        'reason' => 'Confirmed against the signed campus enrollment ledger.',
    ])->assertRedirect();

    expect($record->refresh()->automatic_enrollment_status)->toBe('needs_review')
        ->and($record->final_enrollment_status)->toBe('enrolled')
        ->and($record->final_qualification_status)->toBe('qualified')
        ->and($record->resolved_by)->toBe($registrar->id)
        ->and($record->registrarResolutions()->count())->toBe(1)
        ->and($record->registrarResolutions()->first()->reason)->toContain('signed campus');
});

test('chunk verification sends only campus records and persists separate results and snapshot', function () {
    config(['services.masterlist_verifier.url' => 'http://verifier.test']);
    $campus = Campus::factory()->create();
    $otherCampus = Campus::factory()->create();
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create();
    $batch = MasterlistCampusBatch::create(['masterlist_id' => $masterlist->id, 'campus_id' => $campus->id, 'status' => 'verification_processing']);
    $record = MasterlistRecord::factory()->for($masterlist, 'masterlist')->create(['campus_id' => $campus->id, 'student_id_number' => '2024-001']);
    RegistrarStudent::create(['campus_id' => $campus->id, 'student_id_number' => '2024-001', 'student_name' => 'Ana Cruz', 'enrollment_status' => 'enrolled', 'cor_printed' => true]);
    RegistrarStudent::create(['campus_id' => $otherCampus->id, 'student_id_number' => '2024-002', 'student_name' => 'Other Student', 'enrollment_status' => 'enrolled', 'cor_printed' => true]);
    $run = MasterlistVerificationRun::create(['masterlist_campus_batch_id' => $batch->id, 'status' => 'processing', 'total_records' => 1, 'total_chunks' => 1]);

    Http::fake(['http://verifier.test/verify-masterlist' => Http::response([
        'service_version' => '3.0.0',
        'summary' => ['total_records' => 1],
        'records' => [[
            'row_id' => $record->id, 'match_status' => 'matched', 'enrollment_status' => 'enrolled',
            'cor_status' => 'cor_printed', 'qualification_status' => 'qualified',
            'matched_student_id' => RegistrarStudent::where('campus_id', $campus->id)->value('id'),
            'campus_id' => $campus->id, 'remarks' => 'Confident match.',
        ]],
    ])]);

    app(MasterlistVerificationService::class)->verifyChunk($run, [$record->id]);

    expect($record->refresh()->automatic_enrollment_status)->toBe('enrolled')
        ->and($record->automatic_cor_status)->toBe('cor_printed')
        ->and($record->automatic_qualification_status)->toBe('qualified')
        ->and($record->final_qualification_status)->toBe('qualified')
        ->and($record->verificationSnapshots()->count())->toBe(1);
    Http::assertSent(fn ($request) => count($request['records']) === 1
        && count($request['registrar_students']) === 1
        && $request['records'][0]['campus_id'] === $campus->id);

    app(MasterlistVerificationService::class)->verifyChunk($run, [$record->id]);
    expect($record->verificationSnapshots()->count())->toBe(1);
});

test('verification run owns immutable snapshots and registrar resolutions', function () {
    $campus = Campus::factory()->create();
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create();
    $batch = MasterlistCampusBatch::create([
        'masterlist_id' => $masterlist->id,
        'campus_id' => $campus->id,
        'status' => 'verification_queued',
    ]);
    $record = MasterlistRecord::factory()->for($masterlist, 'masterlist')->create(['campus_id' => $campus->id]);
    $run = MasterlistVerificationRun::create([
        'masterlist_campus_batch_id' => $batch->id,
        'status' => 'queued',
        'total_records' => 1,
        'total_chunks' => 1,
    ]);
    $snapshot = MasterlistRecordVerification::create([
        'masterlist_verification_run_id' => $run->id,
        'masterlist_record_id' => $record->id,
        'original_data' => ['student_name' => $record->student_name],
        'matched_data' => ['student_id_number' => '2024-001'],
        'match_status' => 'matched',
        'enrollment_status' => 'enrolled',
        'cor_status' => 'cor_printed',
        'qualification_status' => 'qualified',
        'result_message' => 'Confident match.',
        'service_version' => '3.0.0',
        'verified_at' => now(),
    ]);

    expect($run->campusBatch->is($batch))->toBeTrue()
        ->and($run->recordVerifications->first()->is($snapshot))->toBeTrue()
        ->and($snapshot->original_data)->toBeArray()
        ->and($snapshot->matched_data)->toBeArray()
        ->and($record->verificationSnapshots()->count())->toBe(1)
        ->and($record->registrarResolutions()->count())->toBe(0)
        ->and(MasterlistRegistrarResolution::query()->count())->toBe(0);
});
