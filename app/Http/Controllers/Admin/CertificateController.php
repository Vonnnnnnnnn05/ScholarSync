<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function index(): View
    {
        $certificates = Certificate::query()
            ->with(['certificateRequest.student.user', 'generatedBy'])
            ->latest('generated_at')
            ->paginate(15);

        return view('admin.certificates.index', [
            'certificates' => $certificates,
        ]);
    }

    public function download(Request $request, Certificate $certificate): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($certificate->file_path), 404);

        return Storage::disk('local')->download(
            $certificate->file_path,
            'certificate-of-no-scholarship-'.$certificate->certificate_number.'.pdf'
        );
    }
}
