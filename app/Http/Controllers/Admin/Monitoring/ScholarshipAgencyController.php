<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScholarshipAgencyController extends Controller
{
    public function index(): View
    {
        return view('admin.monitoring.agencies.index', [
            'agencies' => Agency::query()
                ->withCount('policies')
                ->orderBy('agency_name')
                ->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Agency::create($this->validated($request));

        return back()->with('status', 'Scholarship agency added successfully.');
    }

    public function update(Request $request, Agency $agency): RedirectResponse
    {
        $agency->update($this->validated($request, $agency));

        return back()->with('status', 'Scholarship agency updated successfully.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Agency $agency = null): array
    {
        return $request->validate([
            'agency_name' => ['required', 'string', 'max:255', Rule::unique('agencies', 'agency_name')->ignore($agency)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
