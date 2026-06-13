<?php

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipMasterlist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
