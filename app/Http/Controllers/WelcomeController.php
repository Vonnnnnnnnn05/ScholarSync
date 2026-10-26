<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipPolicy;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WelcomeController extends Controller
{
    public function __invoke(): View
    {
        $scholarships = ScholarshipPolicy::query()
            ->with(['agency', 'program'])
            ->where('status', 'published')
            ->latest()
            ->get();

        return view('welcome', [
            'scholarships' => $scholarships,
        ]);
    }

    public function downloadGuidelines(ScholarshipPolicy $policy): StreamedResponse
    {
        abort_unless($policy->status === 'published', 404);
        abort_unless($policy->file_path && Storage::disk('public')->exists($policy->file_path), 404);

        return Storage::disk('public')->download($policy->file_path);
    }
}
