<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\ScholarshipApplicationStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\CertificateRequest;
use App\Models\MasterlistRecord;
use App\Models\Report;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMasterlist;
use App\Models\ScholarshipProgram;
use App\Models\ScholarshipRequirement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function seedReportRecords(): Student
{
    $student = Student::factory()->create([
        'student_id_number' => 'SKSU-2026-7777',
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
    ]);
    CertificateRequest::factory()->for($student)->create([
        'status' => CertificateRequestStatus::Approved,
        'purpose' => 'For scholarship renewal.',
        'verified_at' => now(),
    ]);
    CertificateRequest::factory()->for($student)->create([
        'status' => CertificateRequestStatus::Rejected,
        'remarks' => 'Invalid OR.',
    ]);

    $agency = Agency::factory()->create(['agency_name' => 'CHED Office']);
    $masterlist = ScholarshipMasterlist::factory()->for($agency)->create([
        'file_name' => 'ched-masterlist.csv',
        'status' => 'released',
        'total_records' => 1,
    ]);
    MasterlistRecord::factory()->for($masterlist, 'masterlist')->create([
        'student_name' => 'Ana Cruz',
        'student_id_number' => 'SKSU-2026-7777',
        'scholarship_program' => 'Merit Scholarship',
        'fund_source' => 'CHED',
        'chairman_status' => 'approved',
    ]);

    $application = ScholarshipApplication::factory()->for($student)->create([
        'scholarship_program' => 'Continuing Merit Scholarship',
        'fund_source' => 'CHED',
        'status' => ScholarshipApplicationStatus::Approved,
        'remarks' => 'Qualified.',
    ]);
    ScholarshipRequirement::factory()->for($application, 'application')->create([
        'requirement_name' => 'Latest Grades',
    ]);
    ScholarshipProgram::factory()->create([
        'name' => 'Tertiary Education Subsidy',
        'fund_source' => 'CHED',
        'agency_name' => 'CHED Office',
    ]);

    return $student;
}

test('administrator can open reports module and preview report data', function () {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    seedReportRecords();

    $this->actingAs($administrator)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('Reports Module')
        ->assertSee('Scholar Information Report');

    $this->actingAs($administrator)
        ->get(route('admin.reports.preview', [
            'type' => 'scholar_information',
            'format' => 'pdf',
            'student' => 'Ana',
        ]))
        ->assertOk()
        ->assertSee('Scholar Information Report')
        ->assertSee('SKSU-2026-7777');
});

test('administrator can export reports as csv excel and pdf', function () {
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    seedReportRecords();

    $this->actingAs($administrator)
        ->get(route('admin.reports.export', [
            'type' => 'certificate_requests',
            'format' => 'csv',
            'status' => CertificateRequestStatus::Approved->value,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs($administrator)
        ->get(route('admin.reports.export', [
            'type' => 'fund_sources',
            'format' => 'excel',
            'fund_source' => 'CHED',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.ms-excel');

    $this->actingAs($administrator)
        ->get(route('admin.reports.export', [
            'type' => 'approved_rejected',
            'format' => 'pdf',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(Report::query()->count())->toBe(3)
        ->and(Report::where('format', 'csv')->exists())->toBeTrue()
        ->and(Report::where('format', 'excel')->exists())->toBeTrue()
        ->and(Report::where('format', 'pdf')->exists())->toBeTrue();
});

test('all configured report types can be previewed', function (string $type) {
    $administrator = User::factory()->role(UserRole::Administrator)->create();
    seedReportRecords();

    $this->actingAs($administrator)
        ->get(route('admin.reports.preview', [
            'type' => $type,
            'format' => 'csv',
        ]))
        ->assertOk()
        ->assertSee('Export Report');
})->with([
    'scholar_information',
    'certificate_requests',
    'or_verification',
    'masterlists',
    'renewal_evaluations',
    'requirement_submissions',
    'fund_sources',
    'approved_rejected',
]);

test('non administrators cannot access reports module', function () {
    $coordinator = User::factory()->role(UserRole::Coordinator)->create();

    $this->actingAs($coordinator)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
