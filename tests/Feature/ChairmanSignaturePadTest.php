<?php

use App\Enums\CertificateRequestStatus;
use App\Enums\UserRole;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\CertificateGenerationService;
use App\Services\SignatureService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Mail::fake();
});

test('scholarship chairman sees digital signature section and pad on profile page', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $response = $this->actingAs($chairman)->get(route('profile.edit'));

    $response->assertOk()
        ->assertSee('Official Digital Signature')
        ->assertSee('Digital Signature Pad')
        ->assertSee('signature_data')
        ->assertSee('Save & Digitize Signature');
});

test('students do not see digital signature pad section on profile page', function () {
    $student = User::factory()->role(UserRole::Student)->create();
    Student::factory()->for($student)->create();

    $response = $this->actingAs($student)->get(route('profile.edit'));

    $response->assertOk()
        ->assertDontSee('Official Digital Signature')
        ->assertDontSee('Digital Signature Pad');
});

test('scholarship chairman can save a digital signature from signature pad base64 data', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    // 1x1 transparent PNG data URI
    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $response = $this->actingAs($chairman)
        ->patch(route('profile.signature.update'), [
            'signature_data' => $sampleSignatureDataUri,
        ]);

    $response->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'signature-updated');

    $chairman->refresh();
    expect($chairman->hasSignature())->toBeTrue()
        ->and($chairman->signature_path)->not->toBeNull()
        ->and($chairman->signature_updated_at)->not->toBeNull();

    Storage::disk('local')->assertExists($chairman->signature_path);
    expect($chairman->signatureDataUri())->toStartWith('data:image/png;base64,');
});

test('scholarship chairman can delete their digital signature', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    app(SignatureService::class)->saveSignatureFromDataUri($chairman, $sampleSignatureDataUri);

    expect($chairman->hasSignature())->toBeTrue();

    $response = $this->actingAs($chairman)
        ->delete(route('profile.signature.destroy'));

    $response->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'signature-deleted');

    $chairman->refresh();
    expect($chairman->hasSignature())->toBeFalse()
        ->and($chairman->signature_path)->toBeNull();
});

test('saving digital signature requires valid data or file', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $response = $this->actingAs($chairman)
        ->patch(route('profile.signature.update'), [
            'signature_data' => '',
        ]);

    $response->assertSessionHasErrors('signature_data');
});

test('scholarship chairman dashboard displays digital signature status banner', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $response = $this->actingAs($chairman)
        ->get(route('dashboard.scholarship-chairman'));

    $response->assertOk()
        ->assertSee('Chairman Digital Signature')
        ->assertSee('Open Signature Pad');
});

test('official receipt show page displays single direct approval with signature pad and no separate verify or button', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Pending)
        ->create();

    $response = $this->actingAs($chairman)
        ->get(route('admin.official-receipts.show', $certificateRequest));

    $response->assertOk()
        ->assertSee('Digital Sign-off & Direct Approval')
        ->assertSee('Digital Signature Pad')
        ->assertSee('Approve & Generate PDF')
        ->assertDontSee('Verify OR');
});

test('chairman can directly approve and generate certificate from pending status in one single action', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Pending)
        ->create();

    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $response = $this->actingAs($chairman)
        ->patch(route('admin.official-receipts.approve', $certificateRequest), [
            'signature_data' => $sampleSignatureDataUri,
        ]);

    $response->assertRedirect(route('admin.official-receipts.show', $certificateRequest))
        ->assertSessionHas('status', 'Request approved and certificate PDF generated.');

    $certificateRequest->refresh();
    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Approved)
        ->and($certificateRequest->approved_by)->toBe($chairman->id)
        ->and($certificateRequest->verified_by)->toBe($chairman->id)
        ->and($certificateRequest->certificate)->not->toBeNull();
});

test('approving certificate request with signature pad drawing stores signature and attaches to generated certificate', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Verified)
        ->create();

    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $response = $this->actingAs($chairman)
        ->patch(route('admin.official-receipts.approve', $certificateRequest), [
            'signature_data' => $sampleSignatureDataUri,
        ]);

    $response->assertRedirect(route('admin.official-receipts.show', $certificateRequest))
        ->assertSessionHas('status', 'Request approved and certificate PDF generated.');

    $certificateRequest->refresh();
    $certificate = $certificateRequest->certificate;

    expect($certificateRequest->status)->toBe(CertificateRequestStatus::Approved)
        ->and($certificateRequest->approved_by)->toBe($chairman->id)
        ->and($certificate)->not->toBeNull()
        ->and($certificate->signature_path)->not->toBeNull();

    Storage::disk('local')->assertExists($certificate->file_path);
    Storage::disk('local')->assertExists($certificate->signature_path);
});

test('approving certificate request uses saved user signature if no new drawing is submitted', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    app(SignatureService::class)->saveSignatureFromDataUri($chairman, $sampleSignatureDataUri);

    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Verified)
        ->create();

    $response = $this->actingAs($chairman)
        ->patch(route('admin.official-receipts.approve', $certificateRequest));

    $response->assertRedirect(route('admin.official-receipts.show', $certificateRequest));

    $certificate = $certificateRequest->fresh()->certificate;

    expect($certificate)->not->toBeNull()
        ->and($certificate->signature_path)->toBe($chairman->signature_path);
});

test('certificate pdf renders digital signature image when present', function () {
    $student = Student::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Clara',
    ]);
    $certificateRequest = CertificateRequest::factory()->for($student)->create();
    $issuedAt = now();

    $signatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    $html = view('certificates.pdf.no-scholarship', [
        'certificateRequest' => $certificateRequest,
        'student' => $student,
        'certificateNumber' => 'CERT-2026-000999',
        'issuedAt' => $issuedAt,
        'semester' => 'First',
        'academicYear' => '2026-2027',
        'signatureImage' => $signatureDataUri,
    ])->render();

    expect($html)
        ->toContain('ADONIS S. BESA, PhD')
        ->toContain('Director, Student Affairs and Services')
        ->toContain($signatureDataUri)
        ->toContain('Authorized Signature');
});

test('official receipt show page contains certificate preview links and embedded viewer', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Pending)
        ->create();

    $response = $this->actingAs($chairman)
        ->get(route('admin.official-receipts.show', $certificateRequest));

    $response->assertOk()
        ->assertSee('Preview Certificate')
        ->assertSee('Certificate Document Preview')
        ->assertSee(route('admin.official-receipts.preview-certificate', $certificateRequest));
});

test('chairman can preview certificate pdf for pending request as a draft preview', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $sampleSignatureDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    app(SignatureService::class)->saveSignatureFromDataUri($chairman, $sampleSignatureDataUri);

    $student = Student::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Pending)
        ->create();

    $response = $this->actingAs($chairman)
        ->get(route('admin.official-receipts.preview-certificate', $certificateRequest));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('chairman can preview finalized certificate pdf for approved request', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $student = Student::factory()->create();
    $certificateRequest = CertificateRequest::factory()
        ->for($student)
        ->status(CertificateRequestStatus::Approved)
        ->create();

    $certificate = app(CertificateGenerationService::class)->generateForRequest($certificateRequest, $chairman);

    $response = $this->actingAs($chairman)
        ->get(route('admin.official-receipts.preview-certificate', $certificateRequest));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

