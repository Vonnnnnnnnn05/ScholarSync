<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function masterlistCsv(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('scholars.csv', implode("\n", [
        'student_id_number,student_name,scholarship_program,fund_source',
        'SKSU-2026-0001,Ana Cruz,Merit Scholarship,CHED',
        'SKSU-2026-0001,Ana Cruz,Merit Scholarship,CHED',
        ',Missing ID,Merit Scholarship,CHED',
        'SKSU-2026-0002,Juan Dela Cruz,,LGU',
    ]));
}

function verificationMasterlistCsv(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('scholars.csv', implode("\n", [
        'student_id_number,student_name,scholarship_program,fund_source',
        'SKSU-2026-1001,Ana Cruz,Merit Scholarship,CHED',
        'SKSU-2026-1002,Juan Dela Cruz,Merit Scholarship,CHED',
    ]));
}

test('scholarship agencies can open the masterlist portal and upload page', function () {
    $user = User::factory()->role(UserRole::ScholarshipAgency)->create();

    $this->actingAs($user)
        ->get(route('agency.masterlists.index'))
        ->assertOk()
        ->assertSee('Scholarship Masterlists')
        ->assertSee('Upload CSV');

    $this->actingAs($user)
        ->get(route('agency.masterlists.create'))
        ->assertOk()
        ->assertSee('Upload Scholar Masterlist')
        ->assertSee('student_id_number');
});

test('agencies can preview csv data with duplicates and invalid fields highlighted', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::ScholarshipAgency)->create();

    $this->actingAs($user)
        ->post(route('agency.masterlists.preview'), [
            'agency_name' => 'SKSU Partner Agency',
            'masterlist' => masterlistCsv(),
        ])
        ->assertOk()
        ->assertSee('Preview Masterlist')
        ->assertSee('Duplicate student ID in uploaded file.')
        ->assertSee('Student Id Number is required.')
        ->assertSee('Scholarship Program is required.')
        ->assertSee('SKSU Partner Agency');
});

test('agencies can import previewed masterlists into records', function () {
    Storage::fake('local');

    $user = User::factory()->role(UserRole::ScholarshipAgency)->create();

    $this->actingAs($user)
        ->post(route('agency.masterlists.preview'), [
            'agency_name' => 'SKSU Partner Agency',
            'masterlist' => masterlistCsv(),
        ])
        ->assertOk();

    $response = $this->actingAs($user)->post(route('agency.masterlists.store'));

    $masterlist = ScholarshipMasterlist::query()->firstOrFail();

    $response->assertRedirect(route('agency.masterlists.show', $masterlist));

    expect($masterlist->agency->user_id)->toBe($user->id)
        ->and($masterlist->total_records)->toBe(4)
        ->and($masterlist->duplicate_count)->toBe(2)
        ->and($masterlist->invalid_count)->toBe(2);

    expect(MasterlistRecord::query()->count())->toBe(4)
        ->and(MasterlistRecord::query()->where('verification_status', 'duplicate')->count())->toBe(2)
        ->and(MasterlistRecord::query()->where('verification_status', 'invalid')->count())->toBe(2);

    Storage::disk('local')->assertExists($masterlist->file_path);
});

test('laravel sends masterlist records to python verifier and stores verification results', function () {
    Storage::fake('local');

    config(['services.masterlist_verifier.url' => 'http://python-verifier.test']);

    $agencyUser = User::factory()->role(UserRole::ScholarshipAgency)->create();
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create([
        'student_id_number' => 'SKSU-2026-1001',
        'first_name' => 'Ana',
        'middle_name' => null,
        'last_name' => 'Cruz',
        'status' => 'active',
    ]);

    Http::fake([
        'python-verifier.test/verify-masterlist' => function (Request $request) use ($student) {
            $payload = $request->data();

            expect($payload['records'])->toHaveCount(2)
                ->and($payload['records'][0])->toMatchArray([
                    'student_id_number' => 'SKSU-2026-1001',
                    'student_name' => 'Ana Cruz',
                    'scholarship_program' => 'Merit Scholarship',
                    'fund_source' => 'CHED',
                ])
                ->and($payload['enrolled_students'][0])->toMatchArray([
                    'id' => $student->id,
                    'student_id_number' => 'SKSU-2026-1001',
                    'student_name' => 'Ana Cruz',
                ]);

            return Http::response([
                'summary' => [
                    'total_records' => 2,
                    'enrolled_count' => 1,
                    'unenrolled_count' => 1,
                    'duplicate_count' => 0,
                    'invalid_count' => 0,
                ],
                'records' => [
                    [
                        'row_id' => $payload['records'][0]['row_id'],
                        'status' => 'enrolled',
                        'matched_student_id' => $student->id,
                        'remarks' => 'Matched enrolled student record.',
                    ],
                    [
                        'row_id' => $payload['records'][1]['row_id'],
                        'status' => 'unenrolled',
                        'matched_student_id' => null,
                        'remarks' => 'No matching enrolled student record found.',
                    ],
                ],
            ]);
        },
    ]);

    $this->actingAs($agencyUser)
        ->post(route('agency.masterlists.preview'), [
            'agency_name' => 'SKSU Partner Agency',
            'masterlist' => verificationMasterlistCsv(),
        ])
        ->assertOk();

    $this->actingAs($agencyUser)->post(route('agency.masterlists.store'));

    $masterlist = ScholarshipMasterlist::query()->firstOrFail();

    expect($masterlist->status)->toBe('verified')
        ->and($masterlist->enrolled_count)->toBe(1)
        ->and($masterlist->unenrolled_count)->toBe(1)
        ->and($masterlist->duplicate_count)->toBe(0)
        ->and($masterlist->invalid_count)->toBe(0);

    expect(MasterlistRecord::where('verification_status', 'enrolled')->first()?->matched_student_id)->toBe($student->id)
        ->and(MasterlistRecord::where('verification_status', 'unenrolled')->exists())->toBeTrue();

    Http::assertSentCount(1);
});

test('non agency users cannot access agency masterlists', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('agency.masterlists.index'))
        ->assertForbidden();
});

test('agencies cannot view another agency masterlist', function () {
    $owner = User::factory()->role(UserRole::ScholarshipAgency)->create();
    $other = User::factory()->role(UserRole::ScholarshipAgency)->create();
    $agency = Agency::factory()->for($owner)->create();
    $masterlist = ScholarshipMasterlist::factory()->for($agency)->create();

    $this->actingAs($other)
        ->get(route('agency.masterlists.show', $masterlist))
        ->assertNotFound();
});
