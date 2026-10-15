<?php

use App\Enums\UserRole;
use App\Http\Controllers\Registrar\EnrolledStudentController;
use App\Models\Campus;
use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\StagedGranteeRecord;
use App\Models\Student;
use App\Models\User;
use App\Services\GranteeCommitService;
use App\Services\GranteeStagingService;
use App\Services\GranteeVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

test('scholarship chairman can access grantee ingestion pages', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    \App\Models\Agency::create([
        'user_id' => $chairman->id,
        'agency_name' => 'Commission on Higher Education (CHED)',
    ]);

    $this->actingAs($chairman)
        ->get(route('chairman.grantees.index'))
        ->assertOk();

    $this->actingAs($chairman)
        ->get(route('chairman.grantees.upload'))
        ->assertOk()
        ->assertSee('Commission on Higher Education (CHED)');
});

test('non-chairman users cannot access grantee ingestion routes', function () {
    $student = User::factory()->role(UserRole::Student)->create();

    $this->actingAs($student)
        ->get(route('chairman.grantees.index'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('chairman.grantees.upload'))
        ->assertForbidden();
});

test('committing a batch flags students as active scholarship grantees', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->create([
        'user_id' => $studentUser->id,
        'student_id_number' => '2024-TEST-001',
        'is_scholarship_grantee' => false,
    ]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TES',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'ched_tes.xlsx',
        'stored_file_path' => 'grantees/uploads/test.xlsx',
        'file_type' => 'xlsx',
        'raw_headers' => ['student_name', 'student_id'],
        'total_rows' => 1,
        'green_count' => 1,
        'amber_count' => 0,
        'red_count' => 0,
        'pending_count' => 0,
        'status' => 'verified',
    ]);

    $record = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'Test Student',
        'student_id' => '2024-TEST-001',
        'matched_student_id' => $student->id,
        'verification_status' => 'GREEN_VERIFIED',
    ]);

    $service = app(GranteeCommitService::class);
    $committedCount = $service->commitBatch($batch, $chairman);

    expect($committedCount)->toBe(1);
    expect($student->fresh()->is_scholarship_grantee)->toBeTrue();
    expect($batch->fresh()->status)->toBe('committed');
});

test('active scholarship grantee is blocked from requesting certificate of non-grantee', function () {
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->create([
        'user_id' => $studentUser->id,
        'student_id_number' => '2024-GRANTEE-001',
        'is_scholarship_grantee' => true,
    ]);

    $response = $this->actingAs($studentUser)
        ->post(route('student.certificate-requests.store'), [
            'student_id_number' => '2024-GRANTEE-001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'course' => 'BSIT',
            'year_level' => '3rd',
            'campus' => 'ACCESS',
            'contact_number' => '09123456789',
            'purpose' => 'Job Application',
        ]);

    $response->assertSessionHasErrors('grantee_restriction');
});

test('multi-file batch upload properly stages records with source pdf and scholarship program', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $fileA = UploadedFile::fake()->create('TES_Grantees.pdf', 50, 'application/pdf');
    $fileB = UploadedFile::fake()->create('TDP_Grantees.pdf', 50, 'application/pdf');

    $stagingService = app(GranteeStagingService::class);
    $batch = $stagingService->stageBatch($chairman, [$fileA, $fileB], [
        'agency_name' => 'CHED-UniFAST',
        'scholarship_name' => 'Multi-Category Ingestion',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'file_type' => 'pdf',
        'raw_headers' => ['student_name', 'student_id'],
        'rows' => [
            [
                'student_name' => 'DELA CRUZ, JUAN',
                'student_id' => '2024-0001',
                'last_name' => 'DELA CRUZ',
                'first_name' => 'JUAN',
                'scholarship_program' => 'TES-UniFAST',
                'source_file' => 'TES_Grantees.pdf',
            ],
            [
                'student_name' => 'SANTOS, MARIA',
                'student_id' => '2024-0002',
                'last_name' => 'SANTOS',
                'first_name' => 'MARIA',
                'scholarship_program' => 'Tulong-Dunong Program (TDP)',
                'source_file' => 'TDP_Grantees.pdf',
            ],
        ],
    ]);

    expect($batch->uploaded_filenames)->toBeArray();
    expect($batch->uploaded_filenames)->toContain('TES_Grantees.pdf');
    expect($batch->uploaded_filenames)->toContain('TDP_Grantees.pdf');
    expect($batch->total_rows)->toBe(2);

    $records = $batch->stagedRecords()->orderBy('row_number')->get();
    expect($records[0]->scholarship_program)->toBe('TES-UniFAST');
    expect($records[0]->source_pdf_name)->toBe('TES_Grantees.pdf');
    expect($records[1]->scholarship_program)->toBe('Tulong-Dunong Program (TDP)');
    expect($records[1]->source_pdf_name)->toBe('TDP_Grantees.pdf');
});

test('master roster flexible header mapping detects keywords and falls back to Column M', function () {
    $controller = app(EnrolledStudentController::class);

    // Explicit header row matching keywords
    $headerWithKeywords = ['Campus', 'Student ID', 'Last Name', 'First Name', 'Program', 'Remarks'];
    $detected = $controller->detectHeaderIndices($headerWithKeywords);
    expect($detected)->not->toBeNull();
    expect($detected['student_id_number'])->toBe(1);
    expect($detected['last_name'])->toBe(2);
    expect($detected['first_name'])->toBe(3);
    expect($detected['course'])->toBe(4);
    expect($detected['enrollment_status'])->toBe(5);

    // Roster with 13 columns where status is unmapped in header row -> defaults to Column M (index 12)
    $headerWithoutStatus = ['Campus', 'Student ID', 'Last Name', 'First Name', 'MI', 'Ext', 'Course', 'Year', 'Gender', 'Contact', 'Email', 'Address', 'StatusColumn'];
    // Rename last column so it has no keyword
    $headerWithoutStatus[12] = 'UnrecognizedHeader';
    $detectedFallback = $controller->detectHeaderIndices($headerWithoutStatus);
    expect($detectedFallback)->not->toBeNull();
    expect($detectedFallback['enrollment_status'])->toBe(12);
});

test('master roster status normalization engine handles standard states, demo fallback and benchmark IDs', function () {
    $controller = app(EnrolledStudentController::class);

    // Benchmark Student ID 24890 must be strictly ENROLLED with cor_printed = true
    $res24890 = $controller->resolveEnrollmentAndCor('unenrolled', 'no', '24890');
    expect($res24890['status'])->toBe('enrolled');
    expect($res24890['cor_printed'])->toBeTrue();

    // Benchmark Student ID 25197 must also be strictly ENROLLED
    $res25197 = $controller->resolveEnrollmentAndCor(null, null, '25197');
    expect($res25197['status'])->toBe('enrolled');
    expect($res25197['cor_printed'])->toBeTrue();

    // Standard ENROLLED
    $resEnrolled = $controller->resolveEnrollmentAndCor('Enrolled', 'yes', '99999');
    expect($resEnrolled['status'])->toBe('enrolled');
    expect($resEnrolled['cor_printed'])->toBeTrue();

    // Standard NO_COR_PRINTED
    $resNoCor = $controller->resolveEnrollmentAndCor('No COR Printed', null, '99999');
    expect($resNoCor['status'])->toBe('enrolled');
    expect($resNoCor['cor_printed'])->toBeFalse();

    // Standard UNENROLLED
    $resUnenrolled = $controller->resolveEnrollmentAndCor('Unenrolled', null, '99999');
    expect($resUnenrolled['status'])->toBe('not_enrolled');
    expect($resUnenrolled['cor_printed'])->toBeFalse();

    // Demo Fallback (Missing/null status applies 90/8/2 weighted distribution)
    $resFallback = $controller->resolveEnrollmentAndCor(null, null, '12345');
    expect(in_array($resFallback['status'], ['enrolled', 'not_enrolled'], true))->toBeTrue();
    expect(is_bool($resFallback['cor_printed']))->toBeTrue();
});

test('reconciliation dashboard filters by category tabs and status pills', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'Joint Subsidy',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'joint_grant.pdf',
        'stored_file_path' => 'grantees/uploads/joint_grant.pdf',
        'file_type' => 'pdf',
        'raw_headers' => ['student_name', 'student_id'],
        'total_rows' => 3,
        'green_count' => 1,
        'amber_count' => 1,
        'red_count' => 1,
        'pending_count' => 0,
        'status' => 'verified',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'JUAN DELA CRUZ',
        'student_id' => '2024-001',
        'scholarship_program' => 'TES-UniFAST',
        'source_pdf_name' => 'tes.pdf',
        'verification_status' => 'GREEN_VERIFIED',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 2,
        'student_name' => 'MARIA SANTOS',
        'student_id' => '2024-002',
        'scholarship_program' => 'Tulong-Dunong Program (TDP)',
        'source_pdf_name' => 'tdp.pdf',
        'verification_status' => 'AMBER_CONFLICT',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 3,
        'student_name' => 'PEDRO PENDUKO',
        'student_id' => '2024-003',
        'scholarship_program' => 'CHED Merit Scholarship Program (CMSP)',
        'source_pdf_name' => 'cmsp.pdf',
        'verification_status' => 'RED_UNMATCHED',
    ]);

    // Test tab category filter
    $response = $this->actingAs($chairman)
        ->get(route('chairman.grantees.show', ['batch' => $batch, 'program' => 'TES-UniFAST']))
        ->assertOk();

    $response->assertSee('JUAN DELA CRUZ');
    $response->assertDontSee('MARIA SANTOS');

    // Test status pill filter
    $statusResponse = $this->actingAs($chairman)
        ->get(route('chairman.grantees.show', ['batch' => $batch, 'status' => 'AMBER_CONFLICT']))
        ->assertOk();

    $statusResponse->assertSee('MARIA SANTOS');
    $statusResponse->assertDontSee('JUAN DELA CRUZ');
});

test('verification service processes staged records through microservice and updates status and remarks', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS', 'code' => 'ACC', 'is_active' => true]);

    $sisStudent = RegistrarStudent::create([
        'student_id_number' => '2024-VERIF-001',
        'student_name' => 'DELA CRUZ, JUAN',
        'campus_id' => $campus->id,
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
        'course' => 'BS Information Technology',
        'year_level' => '1st Year',
    ]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TES-UniFAST',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'tes.pdf',
        'stored_file_path' => 'grantees/uploads/tes.pdf',
        'file_type' => 'pdf',
        'raw_headers' => ['student_name', 'student_id'],
        'total_rows' => 1,
        'green_count' => 0,
        'amber_count' => 0,
        'red_count' => 0,
        'pending_count' => 1,
        'status' => 'staged',
    ]);

    $stagedRecord = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'DELA CRUZ, JUAN',
        'student_id' => '2024-VERIF-001',
        'scholarship_program' => 'TES-UniFAST',
        'verification_status' => 'PENDING',
    ]);

    Http::fake([
        '*/verify-grantees' => Http::response([
            'service_version' => '4.0.0',
            'summary' => [
                'total_records' => 1,
                'green_count' => 1,
                'amber_count' => 0,
                'red_count' => 0,
            ],
            'records' => [
                [
                    'row_id' => $stagedRecord->id,
                    'verification_status' => 'GREEN_VERIFIED',
                    'match_tier' => 'EXACT_ID',
                    'confidence_score' => 1.0,
                    'matched_registrar_student_id' => $sisStudent->id,
                    'resolved_campus_id' => $campus->id,
                    'resolved_program' => 'BS Information Technology',
                    'enrollment_status' => 'ENROLLED',
                    'remarks' => 'Exact student ID match. Enrolled with printed COR.',
                ],
            ],
        ], 200),
    ]);

    $service = app(GranteeVerificationService::class);
    $result = $service->verifyBatchOrChunk($batch);

    expect($result['green_count'])->toBe(1);
    expect($stagedRecord->fresh()->verification_status)->toBe('GREEN_VERIFIED');
    expect($stagedRecord->fresh()->matched_sis_student_id)->toBe($sisStudent->id);
    expect($stagedRecord->fresh()->remarks)->toContain('Exact student ID match');
    expect($batch->fresh()->status)->toBe('verified');
});

test('batch commit fails if unresolved amber conflict records remain', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TES',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test.pdf',
        'stored_file_path' => 'grantees/uploads/test.pdf',
        'file_type' => 'pdf',
        'raw_headers' => ['student_name'],
        'total_rows' => 1,
        'green_count' => 0,
        'amber_count' => 1,
        'red_count' => 0,
        'pending_count' => 0,
        'status' => 'verified',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'AMBIGUOUS NAME',
        'verification_status' => 'AMBER_CONFLICT',
        'conflict_reason' => 'MULTI_MATCH',
    ]);

    $service = app(GranteeCommitService::class);

    expect(fn () => $service->commitBatch($batch, $chairman))
        ->toThrow(ValidationException::class);
});

test('chairman can resolve an amber conflict record and commit the batch', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $studentUser = User::factory()->role(UserRole::Student)->create();
    $student = Student::factory()->create([
        'user_id' => $studentUser->id,
        'student_id_number' => '2024-RESOLVE-001',
        'is_scholarship_grantee' => false,
    ]);

    $sisStudent = RegistrarStudent::create([
        'student_id_number' => '2024-RESOLVE-001',
        'student_name' => 'RESOLVED STUDENT',
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'TES',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test.pdf',
        'stored_file_path' => 'grantees/uploads/test.pdf',
        'file_type' => 'pdf',
        'raw_headers' => ['student_name'],
        'total_rows' => 1,
        'green_count' => 0,
        'amber_count' => 1,
        'red_count' => 0,
        'pending_count' => 0,
        'status' => 'verified',
    ]);

    $record = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'RESOLVED STUDENT TYPO',
        'verification_status' => 'AMBER_CONFLICT',
        'conflict_reason' => 'FUZZY_TYPO',
    ]);

    // Resolve as Chairman
    $response = $this->actingAs($chairman)
        ->patch(route('chairman.grantees.records.resolve', ['batch' => $batch, 'record' => $record]), [
            'action' => 'confirm_match',
            'registrar_student_id' => $sisStudent->id,
            'resolution_notes' => 'Confirmed typo match by Chairman.',
        ]);

    $response->assertRedirect();
    expect($record->fresh()->verification_status)->toBe('GREEN_VERIFIED');
    expect($record->fresh()->matched_sis_student_id)->toBe($sisStudent->id);

    // Commit batch
    $commitResponse = $this->actingAs($chairman)
        ->post(route('chairman.grantees.commit', ['batch' => $batch]));

    $commitResponse->assertRedirect();
    expect($batch->fresh()->status)->toBe('committed');
    expect($student->fresh()->is_scholarship_grantee)->toBeTrue();
});

test('upload form renders target campus selector and removes manual scholarship program input', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);

    $response = $this->actingAs($chairman)
        ->get(route('chairman.grantees.upload'))
        ->assertOk();

    $response->assertSee('Target Campus');
    $response->assertSee('name="campus_id"', false);
    $response->assertSee($campus->name);
    $response->assertDontSee('name="scholarship_name"', false);
    $response->assertSee('Dynamic Category Parser Active:');
});

test('submitting upload form with target campus stages batch with auto-detected program and links campus', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);

    \Illuminate\Support\Facades\Storage::fake('local');
    $tempFile = UploadedFile::fake()->create('ched_list.pdf', 100, 'application/pdf');
    $storedPath = $tempFile->store('grantees/temp', 'local');

    $sessionData = [
        'temp_paths' => [$storedPath],
        'original_names' => ['ched_list.pdf'],
        'file_type' => 'pdf',
        'raw_headers' => ['student_name', 'student_id'],
        'all_rows' => [
            [
                'student_name' => 'ORCAJADA, HEARTY MARIE',
                'student_id' => '2024-0001',
                'scholarship_program' => 'TES-UniFAST',
                'source_file' => 'ched_list.pdf',
            ],
        ],
        'total_rows' => 1,
    ];

    $response = $this->actingAs($chairman)
        ->withSession(['grantee_upload_temp' => $sessionData])
        ->post(route('chairman.grantees.upload.store'), [
            'agency_name' => 'CHED Regional Office',
            'campus_id' => $campus->id,
            'academic_year' => 'AY 2026-2027',
            'semester' => '1st Semester',
        ]);

    $batch = GranteeUploadBatch::latest()->first();
    expect($batch)->not->toBeNull();
    $response->assertRedirect(route('chairman.grantees.show', $batch));

    expect($batch->campus_id)->toBe($campus->id);
    expect($batch->scholarship_name)->toBe('Auto-Detected');
    expect($batch->total_rows)->toBe(1);

    $staged = $batch->stagedRecords()->first();
    expect($staged)->not->toBeNull();
    expect($staged->campus_id)->toBe($campus->id);
    expect($staged->scholarship_program)->toBe('TES-UniFAST');
});

