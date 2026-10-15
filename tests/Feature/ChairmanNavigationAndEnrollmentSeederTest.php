<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Campus;
use App\Models\GranteeUploadBatch;
use App\Models\RegistrarStudent;
use App\Models\ScholarshipPolicy;
use App\Models\ScholarshipProgram;
use App\Models\StagedGranteeRecord;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\EnrollmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChairmanNavigationAndEnrollmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_scholarship_chairman_sidebar_displays_exact_simplified_links(): void
    {
        $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

        $response = $this->actingAs($chairman)->get(route('dashboard.scholarship-chairman'));

        $response->assertOk()
            ->assertSee('id="role-sidebar"', false)
            ->assertSee('Dashboard')
            ->assertSee('Certificate Management')
            ->assertSee(route('admin.official-receipts.index'))
            ->assertSee('Post Scholarship')
            ->assertSee(route('admin.scholarships.index'))
            ->assertSee('Verify Grantees')
            ->assertSee(route('chairman.grantees.index'))
            ->assertSee('Scholars')
            ->assertSee(route('chairman.scholars.index'))
            ->assertSee('Profile')
            ->assertSee(route('profile.edit'))
            // Must not see masterlist uploads or campus progress
            ->assertDontSee('Masterlist Uploads')
            ->assertDontSee('Campus Progress');
    }

    public function test_scholarship_chairman_can_access_and_manage_module_b_scholarships(): void
    {
        $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();

        $agency = Agency::create([
            'agency_name' => 'CHED',
            'status' => 'active',
        ]);
        $program = ScholarshipProgram::create([
            'name' => 'Merit Scholarship',
            'agency_name' => 'CHED',
            'fund_source' => 'CHED',
            'status' => 'active',
        ]);
        ScholarshipPolicy::create([
            'agency_id' => $agency->id,
            'scholarship_program_id' => $program->id,
            'title' => 'CHED Merit Scholarship 2026',
            'application_link' => 'https://ched.gov.ph/cmsp',
            'status' => 'published',
        ]);

        $this->actingAs($chairman)
            ->get(route('admin.scholarships.index'))
            ->assertOk()
            ->assertSee('CHED Merit Scholarship 2026')
            ->assertSee('CHED');

        $this->actingAs($chairman)
            ->post(route('admin.scholarships.store'), [
                'agency' => 'DOST',
                'program' => 'SEI Undergraduate',
                'title' => 'DOST-SEI Undergraduate Scholarship',
                'description' => 'Science and Technology scholarship for college freshmen.',
                'eligibility_requirements' => 'STEM students with 85%+ GWA',
                'documentary_requirements' => 'Form 137, Certificate of Good Moral',
                'deadline' => now()->addMonths(2)->format('Y-m-d'),
                'application_link' => 'https://www.science-scholarships.ph',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.scholarships.index'));

        $this->assertDatabaseHas('scholarship_policies', [
            'title' => 'DOST-SEI Undergraduate Scholarship',
            'application_link' => 'https://www.science-scholarships.ph',
        ]);
    }

    public function test_scholarship_chairman_can_view_and_filter_scholars_directory(): void
    {
        $chairman = User::factory()->role(UserRole::ScholarshipChairman)->create();
        $campusAccess = Campus::factory()->create(['name' => 'ACCESS Campus']);
        $campusIsulan = Campus::factory()->create(['name' => 'Isulan Campus']);

        $batch = GranteeUploadBatch::create([
            'uploaded_by' => $chairman->id,
            'agency_name' => 'UniFAST',
            'scholarship_name' => 'TES-UniFAST',
            'academic_year' => 'AY 2026-2027',
            'semester' => '1st Semester',
            'original_file_name' => 'grantees.xlsx',
            'stored_file_path' => 'grantees/uploads/test.xlsx',
            'file_type' => 'xlsx',
            'status' => 'committed',
            'target_campus_id' => $campusAccess->id,
        ]);

        $studentAccess = Student::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'student_id_number' => 'ACCESS-001',
            'campus' => 'ACCESS Campus',
            'course' => 'BS Information Technology',
            'is_scholarship_grantee' => true,
        ]);

        StagedGranteeRecord::create([
            'batch_id' => $batch->id,
            'row_number' => 1,
            'student_name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'student_id' => 'ACCESS-001',
            'scholarship_name' => 'TES-UniFAST',
            'campus' => 'ACCESS Campus',
            'campus_id' => $campusAccess->id,
            'matched_student_id' => $studentAccess->id,
            'verification_status' => 'GREEN_VERIFIED',
        ]);

        $response = $this->actingAs($chairman)->get(route('chairman.scholars.index'));

        $response->assertOk()
            ->assertSee('Verified Scholarship Grantees Directory')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('ACCESS Campus')
            ->assertSee('TES-UniFAST');

        // Test filtering by program
        $filterResponse = $this->actingAs($chairman)->get(route('chairman.scholars.index', [
            'program' => 'TES-UniFAST',
        ]));
        $filterResponse->assertOk()->assertSee('Juan Dela Cruz');

        // Test filtering by campus
        $campusFilterResponse = $this->actingAs($chairman)->get(route('chairman.scholars.index', [
            'campus' => (string) $campusAccess->id,
        ]));
        $campusFilterResponse->assertOk()->assertSee('Juan Dela Cruz');

        // Test filtering by different campus (should not find)
        $mismatchResponse = $this->actingAs($chairman)->get(route('chairman.scholars.index', [
            'campus' => (string) $campusIsulan->id,
        ]));
        $mismatchResponse->assertOk()->assertDontSee('Juan Dela Cruz');
    }

    public function test_enrollment_seeder_loads_master_student_records_with_expected_distribution(): void
    {
        $this->seed(EnrollmentSeeder::class);

        $total = RegistrarStudent::count();
        $this->assertGreaterThan(3000, $total);

        $enrolledWithCor = RegistrarStudent::where('enrollment_status', 'enrolled')
            ->where('cor_printed', true)
            ->count();
        $noCorPrinted = RegistrarStudent::where('enrollment_status', 'enrolled')
            ->where('cor_printed', false)
            ->count();
        $unenrolled = RegistrarStudent::where('enrollment_status', 'not_enrolled')->count();

        // Check percentages: ~90% enrolled, ~8% no COR, ~2% unenrolled
        $enrolledPct = ($enrolledWithCor / $total) * 100;
        $noCorPct = ($noCorPrinted / $total) * 100;
        $unenrolledPct = ($unenrolled / $total) * 100;

        $this->assertGreaterThanOrEqual(88.0, $enrolledPct);
        $this->assertLessThanOrEqual(93.0, $enrolledPct);

        $this->assertGreaterThanOrEqual(6.0, $noCorPct);
        $this->assertLessThanOrEqual(10.0, $noCorPct);

        $this->assertGreaterThanOrEqual(1.0, $unenrolledPct);
        $this->assertLessThanOrEqual(3.5, $unenrolledPct);

        // Check benchmark IDs 24890 and 25197
        $b1 = RegistrarStudent::where('student_id_number', '24890')->first();
        $this->assertNotNull($b1);
        $this->assertEquals('enrolled', $b1->enrollment_status);
        $this->assertTrue((bool) $b1->cor_printed);

        $b2 = RegistrarStudent::where('student_id_number', '25197')->first();
        $this->assertNotNull($b2);
        $this->assertEquals('enrolled', $b2->enrollment_status);
        $this->assertTrue((bool) $b2->cor_printed);
    }
}
