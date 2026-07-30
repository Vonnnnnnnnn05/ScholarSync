<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ScholarshipPolicy;
use App\Models\ScholarshipProgram;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipPolicyController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $this->agencyFor($request);

        return view('agency.policies.index', [
            'agency' => $agency,
            'programs' => ScholarshipProgram::query()->where('status', 'active')->orderBy('name')->get(),
            'policies' => $agency->policies()->with('program')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, AuditTrailService $audit): RedirectResponse
    {
        $agency = $this->agencyFor($request);
        $validated = $request->validate([
            'scholarship_program_id' => ['nullable', 'integer', 'exists:scholarship_programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'eligibility_requirements' => ['nullable', 'string', 'max:10000'],
            'documentary_requirements' => ['nullable', 'string', 'max:10000'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'policy_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('policy_file')) {
            $validated['file_path'] = $request->file('policy_file')->store('scholarship-policies', 'public');
        }

        unset($validated['policy_file']);

        $policy = $agency->policies()->create($validated);

        $audit->record('scholarship_policy_created', $policy, [
            'agency' => $agency->agency_name,
            'title' => $policy->title,
            'status' => $policy->status,
        ], $request);

        return redirect()->route('agency.policies.index')->with('status', 'Scholarship policy published for student viewing.');
    }

    public function download(Request $request, ScholarshipPolicy $policy): StreamedResponse
    {
        $agency = $this->agencyFor($request);

        abort_unless($policy->agency_id === $agency->id, 404);
        abort_unless($policy->file_path && Storage::disk('public')->exists($policy->file_path), 404);

        return Storage::disk('public')->download($policy->file_path);
    }

    private function agencyFor(Request $request): Agency
    {
        $user = $request->user();

        return Agency::updateOrCreate(
            ['user_id' => $user->id],
            [
                'agency_name' => $user->agency?->agency_name ?: $user->name,
                'contact_person' => $user->name,
                'email' => $user->email,
                'status' => 'active',
            ],
        );
    }
}
