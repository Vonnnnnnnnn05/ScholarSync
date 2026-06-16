<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\Student;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('shows exact live monitoring details on the welcome page', function () {
    $students = Student::factory()->count(2)->create();
    $masterlist = ScholarshipMasterlist::factory()->create();

    CertificateRequest::factory()->for($students->first())->create([
        'status' => CertificateRequestStatus::Pending,
    ]);
    CertificateRequest::factory()->for($students->first())->create([
        'status' => CertificateRequestStatus::Verified,
        'verified_at' => now(),
    ]);
    ScholarshipApplication::factory()->for($students->first())->create([
        'status' => ScholarshipApplicationStatus::Submitted,
    ]);
    ScholarshipApplication::factory()->for($students->last())->create([
        'status' => ScholarshipApplicationStatus::UnderEvaluation,
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'chairman_status' => 'approved',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Live data')
        ->assertSee('Total scholars')
        ->assertSee('Pending certificate requests')
        ->assertSee('Verified official receipts')
        ->assertSee('Uploaded masterlists')
        ->assertSee('Pending evaluations')
        ->assertSee('Approved scholar records')
        ->assertSee('2')
        ->assertSee('1');
});
