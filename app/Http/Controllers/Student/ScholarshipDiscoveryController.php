<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipDiscoveryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        return view('student.scholarships.index', [
            'search' => $search,
            'policies' => ScholarshipPolicy::query()
                ->with(['agency', 'program'])
                ->where('status', 'published')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('agency', fn ($query) => $query->where('agency_name', 'like', "%{$search}%"))
                            ->orWhereHas('program', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function download(ScholarshipPolicy $policy): StreamedResponse
    {
        abort_unless($policy->status === 'published', 404);
        abort_unless($policy->file_path && Storage::disk('public')->exists($policy->file_path), 404);

        return Storage::disk('public')->download($policy->file_path);
    }
}
