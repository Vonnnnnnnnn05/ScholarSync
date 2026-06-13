<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        return view('admin.monitoring.students.index', [
            'students' => Student::query()
                ->with('user')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where('student_id_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Student $student): View
    {
        return view('admin.monitoring.students.show', [
            'student' => $student->load([
                'user',
                'certificateRequests.certificate',
                'scholarshipApplications.requirements',
            ]),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'student_id_number' => ['required', 'string', 'max:255', 'unique:students,student_id_number,'.$student->id],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'year_level' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $student->update($validated);

        return back()->with('status', 'Student record updated successfully.');
    }
}
