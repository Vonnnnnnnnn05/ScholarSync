<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

it('renders the welcome landing page with services and portal actions', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('SKSUScholarSync')
        ->assertSee('Scholarships')
        ->assertSee('Services')
        ->assertSee('Workflow')
        ->assertSee('Portals')
        ->assertSee('Sign In')
        ->assertSee('Sign Up');
});

it('displays published scholarship postings on the homepage for students and visitors', function () {
    $agency = \App\Models\Agency::create([
        'agency_name' => 'CHED Regional Office XII',
        'status' => 'active',
    ]);

    $program = \App\Models\ScholarshipProgram::create([
        'name' => 'TDP-UniFAST Special Grant',
        'fund_source' => 'CHED',
        'agency_name' => 'CHED',
        'status' => 'active',
    ]);

    $policy = \App\Models\ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'scholarship_program_id' => $program->id,
        'title' => 'CHED Tulong Dunong Program 2026',
        'description' => 'Financial assistance for tertiary education students.',
        'eligibility_requirements' => 'Enrolled SKSU student with GWA of 2.0 or higher.',
        'documentary_requirements' => 'Certificate of Registration (COR), Valid Student ID.',
        'deadline' => now()->addDays(30),
        'application_link' => 'https://ched.gov.ph/apply',
        'status' => 'published',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Available Scholarships & Grants')
        ->assertSee('CHED Tulong Dunong Program 2026')
        ->assertSee('CHED Regional Office XII')
        ->assertSee('TDP-UniFAST Special Grant')
        ->assertSee('https://ched.gov.ph/apply');
});

it('allows downloading guidelines for published scholarships when file exists', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::disk('public')->put('guidelines/sample.pdf', 'fake pdf content');

    $agency = \App\Models\Agency::create([
        'agency_name' => 'CHED Regional Office XII',
        'status' => 'active',
    ]);

    $policy = \App\Models\ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'title' => 'CHED Guideline Test',
        'file_path' => 'guidelines/sample.pdf',
        'status' => 'published',
    ]);

    $this->get(route('scholarships.guidelines', $policy))
        ->assertOk();
});

it('returns 404 when downloading guidelines for non-published or non-existent files', function () {
    $agency = \App\Models\Agency::create([
        'agency_name' => 'UniFAST Central',
        'status' => 'active',
    ]);

    $policy = \App\Models\ScholarshipPolicy::create([
        'agency_id' => $agency->id,
        'title' => 'Draft Policy',
        'file_path' => 'guidelines/none.pdf',
        'status' => 'draft',
    ]);

    $this->get(route('scholarships.guidelines', $policy))
        ->assertNotFound();
});


