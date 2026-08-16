<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ScholarshipPolicy;
use App\Models\ScholarshipProgram;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScholarshipOpportunityController extends Controller
{
    public function index(): View
    {
        return view('admin.scholarships.index', [
            'policies' => ScholarshipPolicy::query()->with(['agency', 'program'])->latest()->paginate(15),
        ]);
    }

    public function store(Request $request, AuditTrailService $audit): RedirectResponse
    {
        $policy = $this->persist($this->validated($request));
        $audit->record('scholarship_opportunity_created', $policy, ['title' => $policy->title], $request);

        return redirect()->route('admin.scholarships.index')->with('status', 'Scholarship opportunity saved.');
    }

    public function update(Request $request, ScholarshipPolicy $policy, AuditTrailService $audit): RedirectResponse
    {
        $this->persist($this->validated($request), $policy);
        $audit->record('scholarship_opportunity_updated', $policy, ['title' => $policy->title], $request);

        return redirect()->route('admin.scholarships.index')->with('status', 'Scholarship opportunity updated.');
    }

    public function destroy(Request $request, ScholarshipPolicy $policy, AuditTrailService $audit): RedirectResponse
    {
        $audit->record('scholarship_opportunity_deleted', $policy, ['title' => $policy->title], $request);
        $policy->delete();

        return redirect()->route('admin.scholarships.index')->with('status', 'Scholarship opportunity deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'agency' => ['required', 'string', 'max:255'],
            'program' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'eligibility_requirements' => ['nullable', 'string', 'max:10000'],
            'documentary_requirements' => ['nullable', 'string', 'max:10000'],
            'deadline' => ['nullable', 'date'],
            'application_link' => ['required', 'url:http,https', 'max:2048'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive', 'archived'])],
        ]);
    }

    /** @param array<string, mixed> $validated */
    private function persist(array $validated, ?ScholarshipPolicy $policy = null): ScholarshipPolicy
    {
        return DB::transaction(function () use ($validated, $policy): ScholarshipPolicy {
            $agency = Agency::firstOrCreate(
                ['agency_name' => $validated['agency']],
                ['status' => 'active'],
            );
            $program = ScholarshipProgram::query()
                ->where('name', $validated['program'])
                ->where(function ($query) use ($agency): void {
                    $query->where('agency_name', $agency->agency_name)
                        ->orWhere('fund_source', $agency->agency_name);
                })
                ->first();

            $program ??= ScholarshipProgram::create([
                'name' => $validated['program'],
                'fund_source' => $agency->agency_name,
                'agency_name' => $agency->agency_name,
                'status' => 'active',
            ]);

            $attributes = collect($validated)
                ->except(['agency', 'program'])
                ->merge([
                    'agency_id' => $agency->id,
                    'scholarship_program_id' => $program->id,
                ])->all();

            if ($policy) {
                $policy->update($attributes);

                return $policy->refresh();
            }

            return ScholarshipPolicy::create($attributes);
        });
    }
}
