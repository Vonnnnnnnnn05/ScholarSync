<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CertificateRequest;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('layout shell is role specific and keeps navigation presentable', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('dashboard.student'))
        ->assertOk()
        ->assertSee('id="role-sidebar"', false)
        ->assertDontSee('id="role-navbar"', false)
        ->assertSee('Renewals')
        ->assertSee('Certificates')
        ->assertDontSee('Monitoring')
        ->assertDontSee('Reports');

    $administrator = User::factory()->role(UserRole::Administrator)->create();

    $this->actingAs($administrator)
        ->get(route('dashboard.administrator'))
        ->assertOk()
        ->assertSee('id="role-sidebar"', false)
        ->assertDontSee('id="role-navbar"', false)
        ->assertSee('Monitoring')
        ->assertSee('Student Profiles')
        ->assertSee('Scholar Records')
        ->assertSee('Transactions')
        ->assertSee('Fund Sources')
        ->assertSee('Audit Trail')
        ->assertSee('Reports')
        ->assertSee('Reports Home')
        ->assertSee('Scholar Information')
        ->assertSee('Certificate Requests')
        ->assertSee('Requirement Submissions')
        ->assertSee('Approved and Rejected')
        ->assertSee('Evaluations')
        ->assertSee('Log Out');
});

test('important actions are written to audit trail', function () {
    Storage::fake('local');

    $administrator = User::factory()->role(UserRole::Administrator)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->create(['status' => CertificateRequestStatus::Pending]);

    $this->actingAs($administrator)
        ->patch(route('admin.official-receipts.verify', $certificateRequest))
        ->assertRedirect();

    expect(AuditLog::where('action', 'or_verified')->exists())->toBeTrue();

    $this->actingAs($administrator)
        ->get(route('admin.reports.export', [
            'type' => 'certificate_requests',
            'format' => 'csv',
        ]))
        ->assertOk();

    expect(Report::where('format', 'csv')->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'report_generated')->exists())->toBeTrue();

    $this->actingAs($administrator)
        ->get(route('admin.monitoring.audit.index'))
        ->assertOk()
        ->assertSee('Audit Trail')
        ->assertSee('Or Verified')
        ->assertSee('Report Generated');
});
