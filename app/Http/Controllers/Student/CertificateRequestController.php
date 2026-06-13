<?php

namespace App\Http\Controllers\Student;

use App\Enums\CertificateRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificateRequestRequest;
use App\Models\CertificateRequest;
use App\Models\Student;
use App\Support\SampleCertificateTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateRequestController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;

        $certificateRequests = CertificateRequest::query()
            ->with('certificate')
            ->when($student, fn ($query) => $query->where('student_id', $student->id))
            ->latest()
            ->paginate(10);

        return view('student.certificate-requests.index', [
            'certificateRequests' => $certificateRequests,
            'statuses' => CertificateRequestStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('student.certificate-requests.create', [
            'student' => $request->user()->student,
        ]);
    }

    public function store(StoreCertificateRequestRequest $request): RedirectResponse
    {
        $student = Student::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->studentData() + ['status' => 'active'],
        );

        $officialReceiptPath = $request->file('official_receipt')
            ->store('certificate-requests/official-receipts', 'local');

        $certificateRequest = $student->certificateRequests()->create([
            'purpose' => $request->validated('purpose'),
            'official_receipt' => $officialReceiptPath,
            'status' => CertificateRequestStatus::Pending,
        ]);

        return redirect()
            ->route('student.certificate-requests.show', $certificateRequest)
            ->with('status', 'Certificate request submitted successfully.');
    }

    public function show(Request $request, CertificateRequest $certificateRequest): View
    {
        $this->ensureOwnsRequest($request, $certificateRequest);

        return view('student.certificate-requests.show', [
            'certificateRequest' => $certificateRequest->load(['student', 'certificate']),
            'statuses' => CertificateRequestStatus::cases(),
        ]);
    }

    public function downloadCertificate(Request $request, CertificateRequest $certificateRequest): StreamedResponse
    {
        $this->ensureOwnsRequest($request, $certificateRequest);

        abort_unless($certificateRequest->isCertificateAvailable(), 404);

        $path = $certificateRequest->certificate?->file_path ?? SampleCertificateTemplate::ensureExists();

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            'certificate-of-no-scholarship-'.$certificateRequest->id.'.docx'
        );
    }

    private function ensureOwnsRequest(Request $request, CertificateRequest $certificateRequest): void
    {
        abort_unless(
            $certificateRequest->student()->where('user_id', $request->user()->id)->exists(),
            404
        );
    }
}
