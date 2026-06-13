<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\ScholarshipProgram;
use App\Models\Student;
use App\Models\User;

test('administrator can view central monitoring dashboard summaries', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $student = Student::factory()->create();
    CertificateRequest::factory()->for($student)->create([
        'status' => CertificateRequestStatus::Pending,
    ]);
    CertificateRequest::factory()->for($student)->create([
        'status' => CertificateRequestStatus::Verified,
        'verified_at' => now(),
    ]);
    ScholarshipMasterlist::factory()->create();
    MasterlistRecord::factory()->create(['chairman_status' => 'approved']);
    ScholarshipApplication::factory()->for($student)->create([
        'status' => ScholarshipApplicationStatus::Submitted,
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.dashboard'))
        ->assertOk()
        ->assertSee('Central Monitoring Dashboard')
        ->assertSee('Total Scholars')
        ->assertSee('Pending Certificate Requests')
        ->assertSee('Approved Records');
});

test('administrator can manage student profiles and view histories', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $student = Student::factory()->create([
        'first_name' => 'Maria',
        'middle_name' => null,
        'last_name' => 'Reyes',
        'student_id_number' => 'SKSU-2026-9001',
    ]);
    CertificateRequest::factory()->for($student)->create();
    ScholarshipApplication::factory()->for($student)->create([
        'scholarship_program' => 'Continuing Merit Scholarship',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.students.index', ['search' => 'Maria']))
        ->assertOk()
        ->assertSee('Maria Reyes');

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.students.show', $student))
        ->assertOk()
        ->assertSee('Scholarship History')
        ->assertSee('Certificate Requests');

    $this->actingAs($administrator)
        ->patch(route('admin.monitoring.students.update', $student), [
            'student_id_number' => 'SKSU-2026-9002',
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'course' => 'BSIT',
            'year_level' => '4th Year',
            'campus' => 'ACCESS Campus',
            'contact_number' => '09123456789',
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect($student->refresh()->student_id_number)->toBe('SKSU-2026-9002')
        ->and($student->status)->toBe('inactive');
});

test('administrator can monitor scholar records and transactions', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $agency = Agency::factory()->create(['agency_name' => 'CHED Office']);
    $masterlist = ScholarshipMasterlist::factory()->for($agency)->create([
        'file_name' => 'ched-masterlist.csv',
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_name' => 'Ana Cruz',
        'fund_source' => 'CHED',
        'chairman_status' => 'approved',
    ]);

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.scholars.index', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('Ana Cruz')
        ->assertSee('CHED Office');

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.transactions.index'))
        ->assertOk()
        ->assertSee('Transaction Monitoring')
        ->assertSee('ched-masterlist.csv');
});

test('administrator can manage scholarship programs and fund sources', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->post(route('admin.monitoring.programs.store'), [
            'name' => 'Tertiary Education Subsidy',
            'fund_source' => 'CHED',
            'agency_name' => 'CHED Office',
            'status' => 'active',
        ])
        ->assertRedirect();

    $program = ScholarshipProgram::query()->firstOrFail();

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.programs.index'))
        ->assertOk()
        ->assertSee('Tertiary Education Subsidy')
        ->assertSee('CHED');

    $this->actingAs($administrator)
        ->patch(route('admin.monitoring.programs.update', $program), [
            'name' => 'TES Updated',
            'fund_source' => 'CHED',
            'agency_name' => 'CHED Regional Office',
            'status' => 'inactive',
        ])
        ->assertRedirect();

    expect($program->refresh()->name)->toBe('TES Updated')
        ->and($program->status)->toBe('inactive');
});

test('non administrators cannot access central monitoring', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->get(route('admin.monitoring.dashboard'))
        ->assertForbidden();
});
