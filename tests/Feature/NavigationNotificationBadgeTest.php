<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\CertificateRequest;
use App\Models\GranteeUploadBatch;
use App\Models\Student;
use App\Models\User;

test('chairman sees red notification badge count for pending certificate requests and grantee batches', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $student1 = Student::factory()->create();
    $student2 = Student::factory()->create();

    // 2 pending certificate requests
    CertificateRequest::factory()->for($student1)->status(CertificateRequestStatus::Pending)->create();
    CertificateRequest::factory()->for($student2)->status(CertificateRequestStatus::Verified)->create();

    // 1 pending grantee batch
    GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TDP',
        'academic_year' => '2026-2027',
        'semester' => '1st',
        'original_file_name' => 'ched.xlsx',
        'stored_file_path' => 'batches/ched.xlsx',
        'file_type' => 'xlsx',
        'total_rows' => 50,
        'status' => 'staged',
    ]);

    $response = $this->actingAs($chairman)->get(route($chairman->role->dashboardRouteName()));

    $response->assertOk();
    // Certificate Management badge with count 2
    $response->assertSee('Certificate Management');
    $response->assertSee('2 pending');
    // Verify Grantees badge with count 1
    $response->assertSee('Verify Grantees');
    $response->assertSee('1 pending');

    // Test API endpoint returns JSON counts
    $apiResponse = $this->actingAs($chairman)->getJson(route('notifications.counts'));
    $apiResponse->assertOk()
        ->assertJson([
            'certificate_management' => 2,
            'verify_grantees' => 1,
        ]);
});

test('administrator sees certificate management notification badge for pending requests', function () {
    $admin = User::factory()->role(UserRole::Administrator)->create();
    $student = Student::factory()->create();

    CertificateRequest::factory()->for($student)->status(CertificateRequestStatus::Pending)->create();

    $response = $this->actingAs($admin)->get(route($admin->role->dashboardRouteName()));
    $response->assertOk();
    $response->assertSee('1 pending');

    $apiResponse = $this->actingAs($admin)->getJson(route('notifications.counts'));
    $apiResponse->assertOk()
        ->assertJson([
            'certificate_management' => 1,
        ]);
});

test('coordinator sees campus routing notification badge for unendorsed campus batches', function () {
    $campus = Campus::factory()->create();
    $coordinator = User::factory()->role(UserRole::Coordinator)->for($campus)->create();

    GranteeUploadBatch::create([
        'uploaded_by' => $coordinator->id,
        'campus_id' => $campus->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TDP',
        'academic_year' => '2026-2027',
        'semester' => '1st',
        'original_file_name' => 'campus.xlsx',
        'stored_file_path' => 'batches/campus.xlsx',
        'file_type' => 'xlsx',
        'total_rows' => 10,
        'status' => 'staged',
        'coordinator_endorsed_at' => null,
    ]);

    $response = $this->actingAs($coordinator)->get(route($coordinator->role->dashboardRouteName()));
    $response->assertOk();
    $response->assertSee('Campus Routing');
    $response->assertSee('1 pending');

    $apiResponse = $this->actingAs($coordinator)->getJson(route('notifications.counts'));
    $apiResponse->assertOk()
        ->assertJson([
            'campus_routing' => 1,
        ]);
});

test('registrar sees beneficiary verification notification badge for batches sent to registrar', function () {
    $campus = Campus::factory()->create();
    $registrar = User::factory()->role(UserRole::Registrar)->for($campus)->create();

    GranteeUploadBatch::create([
        'uploaded_by' => $registrar->id,
        'campus_id' => $campus->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TDP',
        'academic_year' => '2026-2027',
        'semester' => '1st',
        'original_file_name' => 'registrar.xlsx',
        'stored_file_path' => 'batches/registrar.xlsx',
        'file_type' => 'xlsx',
        'total_rows' => 10,
        'status' => 'sent_to_registrar',
    ]);

    $response = $this->actingAs($registrar)->get(route($registrar->role->dashboardRouteName()));
    $response->assertOk();
    $response->assertSee('Beneficiary Verification');
    $response->assertSee('1 pending');

    $apiResponse = $this->actingAs($registrar)->getJson(route('notifications.counts'));
    $apiResponse->assertOk()
        ->assertJson([
            'beneficiary_verification' => 1,
        ]);
});

test('student sees certificate notification badge for active requests', function () {
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->for($studentUser)->create();

    CertificateRequest::factory()->for($student)->status(CertificateRequestStatus::Pending)->create();

    $response = $this->actingAs($studentUser)->get(route($studentUser->role->dashboardRouteName()));
    $response->assertOk();
    $response->assertSee('Certificates');
    $response->assertSee('1 pending');

    $apiResponse = $this->actingAs($studentUser)->getJson(route('notifications.counts'));
    $apiResponse->assertOk()
        ->assertJson([
            'student_certificates' => 1,
        ]);
});

test('unauthenticated users cannot access notification counts endpoint', function () {
    $this->getJson(route('notifications.counts'))->assertUnauthorized();
});
