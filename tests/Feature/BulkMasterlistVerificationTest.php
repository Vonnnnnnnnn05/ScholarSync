<?php

use App\Models\Agency;
use App\Models\Campus;
use App\Models\MasterlistCampusBatch;
use App\Models\MasterlistRecord;
use App\Models\MasterlistRecordVerification;
use App\Models\MasterlistRegistrarResolution;
use App\Models\MasterlistVerificationRun;
use App\Models\ScholarshipMasterlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

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
