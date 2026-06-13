<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScholarshipProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.monitoring.programs.index', [
            'programs' => ScholarshipProgram::query()
                ->latest()
                ->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ScholarshipProgram::create($this->validated($request));

        return back()->with('status', 'Scholarship program added successfully.');
    }

    public function update(Request $request, ScholarshipProgram $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        return back()->with('status', 'Scholarship program updated successfully.');
    }

    public function destroy(ScholarshipProgram $program): RedirectResponse
    {
        $program->delete();

        return back()->with('status', 'Scholarship program removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ScholarshipProgram $program = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fund_source' => ['required', 'string', 'max:255'],
            'agency_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);
    }
}
