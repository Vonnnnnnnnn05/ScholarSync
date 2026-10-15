<?php

use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\StagedGranteeRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('verify grantees upload page renders simplified step 1 with category notice and no top metadata clutter', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);

    $response = $this->actingAs($chairman)
        ->get(route('chairman.grantees.upload'))
        ->assertOk();

    // Step 1 only has drag and drop file upload and banner
    $response->assertSee('Masterlist File Selection & Upload');
    $response->assertSee('Dynamic Category Parser Active:');
    $response->assertSee('Masterlist File (.pdf, .xlsx, .csv)');
    $response->assertDontSee('name="scholarship_name"', false);

    // Fallback campus selector is in preview/reconciliation toolbar
    $response->assertSee('Target Campus / Fallback (if missing in file & DB)');
    $response->assertSee('name="campus_id"', false);
    $response->assertSee($campus->name);
});

test('ajax parse auto-resolves sis students, calculates category counts, and sets enrollment statuses', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);

    // Create enrolled student with multi-word first name
    $sisStudent = RegistrarStudent::create([
        'student_id_number' => '24016',
        'student_name' => 'ABAD, PRECIOUS ELLA M.',
        'campus' => 'ACCESS Campus',
        'campus_id' => $campus->id,
        'course' => 'BSED-Mathematics',
        'year_level' => '3rd',
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    // Mock microservice parse response
    Http::fake([
        '*/parse-file' => Http::response([
            'success' => true,
            'file_type' => 'pdf',
            'original_headers' => [],
            'normalized_headers' => ['student_name', 'first_name', 'last_name', 'scholarship_program'],
            'missing_fields' => [],
            'unmapped_headers' => [],
            'rows' => [
                [
                    'row_number' => 1,
                    'student_name' => 'PRECIOUS ELLA ABAD',
                    'first_name' => 'PRECIOUS ELLA',
                    'last_name' => 'ABAD',
                    'raw_agency_name' => 'ABAD, PRECIOUS ELLA',
                    'scholarship_program' => 'TES-UniFAST',
                    'source_pdf_name' => 'masterlist.pdf',
                ],
                [
                    'row_number' => 2,
                    'student_name' => 'UNKNOWN STUDENT',
                    'first_name' => 'UNKNOWN',
                    'last_name' => 'STUDENT',
                    'raw_agency_name' => 'STUDENT, UNKNOWN',
                    'scholarship_program' => 'TDP',
                    'source_pdf_name' => 'masterlist.pdf',
                ],
                [
                    'row_number' => 3,
                    'student_name' => 'MERIT SCHOLAR',
                    'first_name' => 'MERIT',
                    'last_name' => 'SCHOLAR',
                    'raw_agency_name' => 'SCHOLAR, MERIT',
                    'scholarship_program' => 'CMSP',
                    'source_pdf_name' => 'masterlist.pdf',
                ],
            ],
            'total_rows' => 3,
            'parse_warnings' => [],
        ], 200),
    ]);

    $file = UploadedFile::fake()->create('masterlist.pdf', 100, 'application/pdf');

    $response = $this->actingAs($chairman)
        ->postJson(route('chairman.grantees.upload.parse'), [
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'total_rows' => 3,
                'matched_count' => 1,
                'unmatched_count' => 2,
                'category_counts' => [
                    'TES-UniFAST' => 1,
                    'TDP' => 1,
                    'CMSP' => 1,
                ],
            ],
        ]);

    $allRows = $response->json('data.all_rows');
    expect($allRows[0]['student_id'])->toBe('24016');
    expect($allRows[0]['first_name'])->toBe('PRECIOUS ELLA');
    expect($allRows[0]['campus'])->toBe('ACCESS Campus');
    expect($allRows[0]['program'])->toBe('BSED-Mathematics');
    expect($allRows[0]['enrollment_status'])->toBe('ENROLLED');
    expect($allRows[0]['verification_status'])->toBe('GREEN_VERIFIED');

    // Unmatched student has null campus and pending status
    expect($allRows[1]['student_id'])->toBeNull();
    expect($allRows[1]['campus'])->toBeNull();
    expect($allRows[1]['verification_status'])->toBe('PENDING');
});

test('chairman can assign campus to a single staged record and deliver to coordinator', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $isulanCampus = Campus::create(['name' => 'Isulan Campus', 'code' => 'ISU', 'is_active' => true]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'Auto-Detected',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test.pdf',
        'stored_file_path' => 'grantees/test.pdf',
        'file_type' => 'pdf',
        'total_rows' => 1,
        'status' => 'staged',
    ]);

    $record = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'Juan Dela Cruz',
        'verification_status' => 'PENDING',
    ]);

    $response = $this->actingAs($chairman)
        ->patch(route('chairman.grantees.records.assign-campus', ['batch' => $batch, 'record' => $record]), [
            'campus_id' => $isulanCampus->id,
        ]);

    $response->assertRedirect();
    expect($record->fresh()->campus_id)->toBe($isulanCampus->id);
    expect($record->fresh()->campus)->toBe('Isulan Campus');
    expect($record->fresh()->remarks)->toContain('routed to Campus Coordinator');
});

test('chairman can bulk assign fallback campus to all unassigned records in batch', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'Tacurong Campus', 'code' => 'TAC', 'is_active' => true]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'Auto-Detected',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test.pdf',
        'stored_file_path' => 'grantees/test.pdf',
        'file_type' => 'pdf',
        'total_rows' => 2,
        'status' => 'staged',
    ]);

    $rec1 = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'Student One',
        'verification_status' => 'PENDING',
    ]);

    $rec2 = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 2,
        'student_name' => 'Student Two',
        'verification_status' => 'PENDING',
    ]);

    $response = $this->actingAs($chairman)
        ->post(route('chairman.grantees.assign-fallback-campus', ['batch' => $batch]), [
            'campus_id' => $campus->id,
        ]);

    $response->assertRedirect();
    expect($rec1->fresh()->campus_id)->toBe($campus->id);
    expect($rec1->fresh()->campus)->toBe($campus->name);
    expect($rec2->fresh()->campus_id)->toBe($campus->id);
    expect($rec2->fresh()->campus)->toBe($campus->name);
});

test('chairman can bulk assign campus to ALL records in batch with apply_scope=all', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campusAcc = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $campusIsu = Campus::create(['name' => 'Isulan Campus', 'code' => 'ISU', 'is_active' => true]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'Auto-Detected',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test.pdf',
        'stored_file_path' => 'grantees/test.pdf',
        'file_type' => 'pdf',
        'total_rows' => 2,
        'status' => 'staged',
    ]);

    // Rec1 already has ACCESS Campus; Rec2 is unassigned
    $rec1 = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_name' => 'Student One',
        'campus_id' => $campusAcc->id,
        'campus' => $campusAcc->name,
        'verification_status' => 'GREEN_VERIFIED',
    ]);

    $rec2 = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 2,
        'student_name' => 'Student Two',
        'campus_id' => null,
        'campus' => null,
        'verification_status' => 'PENDING',
    ]);

    // Apply to ALL records with apply_scope=all -> both records become Isulan Campus
    $response = $this->actingAs($chairman)
        ->post(route('chairman.grantees.assign-fallback-campus', ['batch' => $batch]), [
            'campus_id' => $campusIsu->id,
            'apply_scope' => 'all',
        ]);

    $response->assertRedirect();
    expect($rec1->fresh()->campus_id)->toBe($campusIsu->id);
    expect($rec1->fresh()->campus)->toBe($campusIsu->name);
    expect($rec2->fresh()->campus_id)->toBe($campusIsu->id);
    expect($rec2->fresh()->campus)->toBe($campusIsu->name);
    expect($batch->fresh()->campus_id)->toBe($campusIsu->id);
});

test('upload store applies campus destination to ALL records in batch when apply_campus_to_all is true', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $tacurong = Campus::create(['name' => 'Tacurong Campus', 'code' => 'TAC', 'is_active' => true]);

    \Illuminate\Support\Facades\Storage::fake('local');
    $fakePath = \Illuminate\Support\Facades\Storage::disk('local')->put('grantees/temp/test.pdf', 'dummy content');

    session([
        'grantee_upload_temp' => [
            'temp_paths' => ['grantees/temp/test.pdf'],
            'original_names' => ['test.pdf'],
            'file_type' => 'pdf',
            'raw_headers' => [],
            'all_rows' => [
                ['row_number' => 1, 'student_name' => 'Student A', 'campus_id' => $campus->id, 'campus' => $campus->name],
                ['row_number' => 2, 'student_name' => 'Student B', 'campus_id' => null, 'campus' => null],
            ],
            'total_rows' => 2,
            'category_counts' => ['TES-UniFAST' => 2],
            'matched_count' => 1,
        ],
    ]);

    $response = $this->actingAs($chairman)
        ->post(route('chairman.grantees.upload.store'), [
            'campus_id' => $tacurong->id,
            'apply_campus_to_all' => 1,
        ]);

    $response->assertRedirect();
    $batch = GranteeUploadBatch::latest('id')->first();
    expect($batch)->not->toBeNull();
    expect($batch->campus_id)->toBe($tacurong->id);

    $records = $batch->stagedRecords()->orderBy('row_number')->get();
    expect($records[0]->campus_id)->toBe($tacurong->id);
    expect($records[0]->campus)->toBe($tacurong->name);
    expect($records[1]->campus_id)->toBe($tacurong->id);
    expect($records[1]->campus)->toBe($tacurong->name);
});

test('complete 4-step verification workflow: chairman to coordinator, coordinator to registrar, registrar verifies, coordinator passes to chairman', function () {
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $coordinator = User::factory()->role(UserRole::Coordinator)->create(['campus_id' => $campus->id]);
    $registrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $campus->id]);

    $otherCampus = Campus::create(['name' => 'Isulan Campus', 'code' => 'ISU', 'is_active' => true]);
    $otherRegistrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $otherCampus->id]);

    // Seed official campus SIS enrollment record
    $sisStudent = \App\Models\RegistrarStudent::create([
        'student_id_number' => 'SKSU-2026-001',
        'student_name' => 'Precious Ella Abad',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'course' => 'BS Information Technology',
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'campus_id' => $campus->id,
        'agency_name' => 'CHED-UniFAST',
        'scholarship_name' => 'Auto-Detected',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'agency_list.pdf',
        'stored_file_path' => 'grantees/agency_list.pdf',
        'file_type' => 'pdf',
        'total_rows' => 1,
        'status' => 'staged',
    ]);

    $rec1 = StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_id' => 'SKSU-2026-001',
        'student_name' => 'Precious Ella Abad',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'scholarship_program' => 'TES-UniFAST',
        'verification_status' => 'PENDING',
    ]);

    // 1. Chairman delivers batch to Campus Coordinator
    $this->actingAs($chairman)
        ->post(route('chairman.grantees.deliver-to-coordinator', $batch))
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('delivered_to_coordinator');
    expect($batch->fresh()->delivered_to_coordinator_at)->not->toBeNull();

    // 2. Coordinator views batch and forwards it to Campus Registrar
    $this->actingAs($coordinator)
        ->get(route('coordinator.grantees.show', $batch))
        ->assertOk()
        ->assertSee('Forward to Campus Registrar');

    $this->actingAs($coordinator)
        ->post(route('coordinator.grantees.forward-to-registrar', $batch), [
            'notes' => 'Please verify enrollment and COR status.',
        ])
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('sent_to_registrar');
    expect($batch->fresh()->sent_to_registrar_at)->not->toBeNull();
    expect($batch->fresh()->sent_to_registrar_by)->toBe($coordinator->id);

    // 3. Unauthorized Campus Registrar is forbidden (403)
    $this->actingAs($otherRegistrar)
        ->get(route('registrar.grantees.show', $batch))
        ->assertForbidden();

    // 4. Assigned Registrar receives batch and runs enrollment verification
    $this->actingAs($registrar)
        ->get(route('registrar.grantees.show', $batch))
        ->assertOk()
        ->assertSee('Run Official Enrollment Verification')
        ->assertSee('Precious Ella Abad');

    $this->actingAs($registrar)
        ->post(route('registrar.grantees.verify-batch', $batch))
        ->assertRedirect();

    expect($rec1->fresh()->verification_status)->toBe('GREEN_VERIFIED');
    expect($rec1->fresh()->matched_sis_student_id)->toBe($sisStudent->id);
    expect($batch->fresh()->green_count)->toBe(1);

    // 5. Registrar returns verified roster to Coordinator
    $this->actingAs($registrar)
        ->post(route('registrar.grantees.return-to-coordinator', $batch), [
            'notes' => 'Enrollment verified. Student is enrolled with printed COR.',
        ])
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('verified_by_registrar');
    expect($batch->fresh()->registrar_verified_at)->not->toBeNull();
    expect($batch->fresh()->registrar_verified_by)->toBe($registrar->id);

    // 6. Coordinator reviews registrar results and passes to Chairman
    $this->actingAs($coordinator)
        ->get(route('coordinator.grantees.show', $batch))
        ->assertOk()
        ->assertSee('Registrar Verification Complete')
        ->assertSee('Pass Verified Roster to Scholarship Chairman');

    $this->actingAs($coordinator)
        ->post(route('coordinator.grantees.endorse', $batch), [
            'notes' => 'Coordinator reviewed registrar findings and passed to Chairman.',
        ])
        ->assertRedirect();

    expect($batch->fresh()->status)->toBe('endorsed_to_chairman');
    expect($batch->fresh()->coordinator_endorsed_at)->not->toBeNull();

    // 7. Chairman view reflects full status trace
    $chairmanView = $this->actingAs($chairman)
        ->get(route('chairman.grantees.show', $batch))
        ->assertOk();

    $chairmanView->assertSee('Delivered to');
    $chairmanView->assertSee('Verified by Registrar');
    $chairmanView->assertSee('Endorsed by Coordinator');
});

test('chairman, coordinator, and registrar can export grantee roster to excel format', function () {
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $otherCampus = Campus::create(['name' => 'Isulan Campus', 'code' => 'ISU', 'is_active' => true]);

    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
    $coordinator = User::factory()->role(UserRole::Coordinator)->create(['campus_id' => $campus->id]);
    $registrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $campus->id]);
    $otherRegistrar = User::factory()->role(UserRole::Registrar)->create(['campus_id' => $otherCampus->id]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'campus_id' => $campus->id,
        'agency_name' => 'CHED',
        'scholarship_name' => 'CHED TDP',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'ched_grantees.xlsx',
        'stored_file_path' => 'grantees/ched_grantees.xlsx',
        'file_type' => 'xlsx',
        'total_rows' => 1,
        'status' => 'endorsed_to_chairman',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_id' => '2023-0001',
        'student_name' => 'DELA CRUZ, JUAN',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'verification_status' => 'GREEN_VERIFIED',
    ]);

    // 1. Chairman can export to Excel
    $chairmanResponse = $this->actingAs($chairman)
        ->get(route('chairman.grantees.export', $batch));

    $chairmanResponse->assertOk();
    expect($chairmanResponse->headers->get('content-type'))->toContain('spreadsheetml.sheet');
    expect($chairmanResponse->headers->get('content-disposition'))->toContain('.xlsx');

    // 2. Coordinator can export to Excel
    $coordinatorResponse = $this->actingAs($coordinator)
        ->get(route('coordinator.grantees.export', $batch));

    $coordinatorResponse->assertOk();
    expect($coordinatorResponse->headers->get('content-type'))->toContain('spreadsheetml.sheet');

    // 3. Registrar can export to Excel
    $registrarResponse = $this->actingAs($registrar)
        ->get(route('registrar.grantees.export', $batch));

    $registrarResponse->assertOk();
    expect($registrarResponse->headers->get('content-type'))->toContain('spreadsheetml.sheet');

    // 4. Unauthorized campus registrar is rejected
    $this->actingAs($otherRegistrar)
        ->get(route('registrar.grantees.export', $batch))
        ->assertForbidden();
});

test('excel export contains Remarks column with Eligible or Not Eligible based on enrollment and COR', function () {
    $campus = Campus::first() ?? Campus::create(['name' => 'ACCESS Campus (Main)', 'code' => 'ACC', 'is_active' => true]);
    $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

    // 1. Enrolled with printed COR -> Eligible
    $studentEligible = \App\Models\RegistrarStudent::create([
        'student_id_number' => 'SKSU-EL-001',
        'student_name' => 'JUAN DELA CRUZ',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'course' => 'BSIT',
        'enrollment_status' => 'enrolled',
        'cor_printed' => true,
    ]);

    // 2. Enrolled with NO printed COR -> Not Eligible
    $studentNoCor = \App\Models\RegistrarStudent::create([
        'student_id_number' => 'SKSU-NOCOR-002',
        'student_name' => 'MARIA CLARA',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'course' => 'BSCS',
        'enrollment_status' => 'enrolled',
        'cor_printed' => false,
    ]);

    // 3. Unenrolled student -> Not Eligible
    $studentUnenrolled = \App\Models\RegistrarStudent::create([
        'student_id_number' => 'SKSU-UN-003',
        'student_name' => 'PEDRO PENDUKO',
        'campus_id' => $campus->id,
        'campus' => $campus->name,
        'course' => 'BSCRIM',
        'enrollment_status' => 'unenrolled',
        'cor_printed' => false,
    ]);

    $batch = GranteeUploadBatch::create([
        'uploaded_by' => $chairman->id,
        'campus_id' => $campus->id,
        'agency_name' => 'CHED-UniFAST',
        'scholarship_name' => 'TDP',
        'academic_year' => 'AY 2026-2027',
        'semester' => '1st Semester',
        'original_file_name' => 'test_eligibility.xlsx',
        'stored_file_path' => 'grantees/test_eligibility.xlsx',
        'file_type' => 'xlsx',
        'total_rows' => 3,
        'status' => 'verified_by_registrar',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 1,
        'student_id' => 'SKSU-EL-001',
        'student_name' => 'JUAN DELA CRUZ',
        'matched_sis_student_id' => $studentEligible->id,
        'verification_status' => 'GREEN_VERIFIED',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 2,
        'student_id' => 'SKSU-NOCOR-002',
        'student_name' => 'MARIA CLARA',
        'matched_sis_student_id' => $studentNoCor->id,
        'verification_status' => 'AMBER_CONFLICT',
        'conflict_reason' => 'Enrolled but No COR Printed',
    ]);

    StagedGranteeRecord::create([
        'batch_id' => $batch->id,
        'row_number' => 3,
        'student_id' => 'SKSU-UN-003',
        'student_name' => 'PEDRO PENDUKO',
        'matched_sis_student_id' => $studentUnenrolled->id,
        'verification_status' => 'RED_UNMATCHED',
        'conflict_reason' => 'Student Not Enrolled',
    ]);

    $response = $this->actingAs($chairman)
        ->get(route('chairman.grantees.export', $batch));

    $response->assertOk();

    // Stream to temp file and parse using PhpSpreadsheet
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test_');
    file_put_contents($tempFile, $response->streamedContent());

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($tempFile);
    $sheet = $spreadsheet->getActiveSheet();

    // Headers row (row 5)
    expect($sheet->getCell('L5')->getValue())->toBe('Remarks');
    expect($sheet->getCell('M5')->getValue())->toBe('Remarks Details / Reason');

    // Row 6: Enrolled + COR Printed -> Eligible
    expect($sheet->getCell('L6')->getValue())->toBe('Eligible');
    expect($sheet->getCell('M6')->getValue())->toBe('Enrolled with Printed COR');

    // Row 7: Enrolled + No COR Printed -> Not Eligible
    expect($sheet->getCell('L7')->getValue())->toBe('Not Eligible');
    expect($sheet->getCell('M7')->getValue())->toBe('No COR Printed');

    // Row 8: Unenrolled -> Not Eligible
    expect($sheet->getCell('L8')->getValue())->toBe('Not Eligible');
    expect($sheet->getCell('M8')->getValue())->toContain('Not Enrolled');

    unlink($tempFile);
});


