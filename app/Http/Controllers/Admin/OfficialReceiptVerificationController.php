<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use App\Services\AuditTrailService;
use App\Services\CertificateGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficialReceiptVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $certificateRequests = CertificateRequest::query()
            ->with(['student.user', 'certificate'])
            ->when(
                in_array($status, CertificateRequestStatus::values(), true),
                fn ($query) => $query->where('status', $status),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.official-receipts.index', [
            'certificateRequests' => $certificateRequests,
            'statuses' => CertificateRequestStatus::cases(),
            'activeStatus' => $status,
        ]);
    }

    public function show(CertificateRequest $certificateRequest): View
    {
        return view('admin.official-receipts.show', [
            'certificateRequest' => $certificateRequest->load(['student.user', 'certificate']),
        ]);
    }

    public function previewCertificate(
        Request $request,
        CertificateRequest $certificateRequest,
        CertificateGenerationService $certificateGenerator,
    ): Response {
        $certificateRequest->loadMissing(['student.user', 'certificate']);

        if ($certificateRequest->isCertificateAvailable() && Storage::disk('local')->exists($certificateRequest->certificate->file_path)) {
            return Storage::disk('local')->response(
                $certificateRequest->certificate->file_path,
                'certificate-'.$certificateRequest->certificate->certificate_number.'.pdf',
                ['Content-Type' => 'application/pdf'],
                'inline'
            );
        }

        $pdf = $certificateGenerator->previewPdfForRequest($certificateRequest, $request->user());

        return $pdf->stream('certificate-preview-'.$certificateRequest->id.'.pdf');
    }


    public function download(CertificateRequest $certificateRequest): StreamedResponse
    {
        abort_unless($certificateRequest->official_receipt, 404);
        abort_unless(Storage::disk('local')->exists($certificateRequest->official_receipt), 404);

        return Storage::disk('local')->download($certificateRequest->official_receipt);
    }

    public function view(CertificateRequest $certificateRequest): StreamedResponse
    {
        abort_unless($certificateRequest->official_receipt, 404);
        abort_unless(Storage::disk('local')->exists($certificateRequest->official_receipt), 404);

        return Storage::disk('local')->response(
            $certificateRequest->official_receipt,
            basename($certificateRequest->official_receipt),
            [],
            'inline'
        );
    }

    public function verify(Request $request, CertificateRequest $certificateRequest, AuditTrailService $audit): RedirectResponse
    {
        $certificateRequest->update([
            'status' => CertificateRequestStatus::Verified,
            'remarks' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $audit->record('or_verified', $certificateRequest, [
            'student_id' => $certificateRequest->student_id,
            'status' => CertificateRequestStatus::Verified->value,
        ], $request);

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Official Receipt verified successfully.');
    }

    public function approve(
        Request $request,
        CertificateRequest $certificateRequest,
        AuditTrailService $audit,
        CertificateGenerationService $certificateGenerator,
        \App\Services\SignatureService $signatureService,
    ): RedirectResponse {
        if ($request->filled('signature_data')) {
            $signatureService->saveSignatureFromDataUri($request->user(), $request->input('signature_data'));
        } elseif ($request->hasFile('signature_file')) {
            $signatureService->saveSignatureFromUploadedFile($request->user(), $request->file('signature_file'));
        }

        if ($certificateRequest->status === CertificateRequestStatus::Approved) {
            $alreadyGenerated = $certificateRequest->certificate()->exists();
            $certificateGenerator->generateForRequest($certificateRequest, $request->user());

            return redirect()
                ->route('admin.official-receipts.show', $certificateRequest)
                ->with('status', $alreadyGenerated
                    ? 'Certificate PDF is already generated.'
                    : 'Certificate PDF generated successfully.');
        }

        abort_unless(in_array($certificateRequest->status, [CertificateRequestStatus::Pending, CertificateRequestStatus::Verified], true), 422);

        $certificateRequest->update([
            'status' => CertificateRequestStatus::Approved,
            'verified_by' => $certificateRequest->verified_by ?? $request->user()->id,
            'verified_at' => $certificateRequest->verified_at ?? now(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->record('certificate_request_approved', $certificateRequest, [
            'student_id' => $certificateRequest->student_id,
            'status' => CertificateRequestStatus::Approved->value,
            'has_signature' => $request->user()->hasSignature(),
        ], $request);

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Request approved and certificate PDF generated.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest, AuditTrailService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $certificateRequest->update([
            'status' => CertificateRequestStatus::Rejected,
            'remarks' => $validated['remarks'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        $audit->record('or_rejected', $certificateRequest, [
            'student_id' => $certificateRequest->student_id,
            'remarks' => $validated['remarks'],
            'status' => CertificateRequestStatus::Rejected->value,
        ], $request);

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Official Receipt rejected and student notified.');
    }
}
