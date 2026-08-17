<?php

use App\Models\Agency;
use App\Models\Campus;
use App\Models\MasterlistRecord;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipMasterlist;
use App\Services\MasterlistCsvService;
use App\Services\MasterlistVerificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('verification sends names and registrar COR data then persists no COR result', function () {
    config(['services.masterlist_verifier.url' => 'http://verifier.test']);
    $campus = Campus::factory()->create();
    $masterlist = ScholarshipMasterlist::factory()->for(Agency::factory())->create();
    $record = MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_name' => 'Ana Cruz',
        'student_id_number' => null,
    ]);
    $registrarStudent = RegistrarStudent::create([
        'student_id_number' => 'SKSU-1',
        'student_name' => 'Ana Cruz',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'enrollment_status' => 'enrolled',
        'cor_printed' => false,
    ]);

    Http::fake(fn ($request) => Http::response([
        'summary' => [
            'total_records' => 1,
            'enrolled_count' => 0,
            'no_cor_printed_count' => 1,
            'unenrolled_count' => 0,
        ],
        'records' => [[
            'row_id' => $record->id,
            'status' => 'no_cor_printed',
            'matched_student_id' => $registrarStudent->id,
            'campus_id' => $campus->id,
            'remarks' => 'COR has not been printed.',
        ]],
    ]));

    app(MasterlistVerificationService::class)->verify($masterlist);

    Http::assertSent(fn ($request) => $request['records'] === [[
        'row_id' => $record->id,
        'student_name' => 'Ana Cruz',
    ]] && $request['registrar_students'][0]['cor_printed'] === false);

    expect($record->refresh()->verification_status)->toBe('no_cor_printed')
        ->and($record->registrar_student_id)->toBe($registrarStudent->id)
        ->and($record->campus_id)->toBe($campus->id)
        ->and($masterlist->refresh()->no_cor_printed_count)->toBe(1);
});

test('masterlist preview requires student name and campus columns', function () {
    Storage::fake('local');
    $campus = Campus::factory()->create(['name' => 'Isulan Campus', 'code' => 'isulan']);
    Storage::disk('local')->put('masterlists/tmp/names.csv', "student_name,campus\nAna Cruz,Isulan Campus\nJuan Dela Cruz,isulan\n");

    $preview = app(MasterlistCsvService::class)->preview('masterlists/tmp/names.csv');

    expect(MasterlistCsvService::REQUIRED_COLUMNS)->toBe(['student_name', 'campus'])
        ->and($preview['missing_columns'])->toBe([])
        ->and($preview['total_records'])->toBe(2)
        ->and($preview['rows'][0]['student_name'])->toBe('Ana Cruz')
        ->and($preview['rows'][0]['campus_id'])->toBe($campus->id);
});
