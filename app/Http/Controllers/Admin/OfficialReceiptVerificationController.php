<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CertificateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
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

    public function download(CertificateRequest $certificateRequest): StreamedResponse
    {
        abort_unless($certificateRequest->official_receipt, 404);
        abort_unless(Storage::disk('local')->exists($certificateRequest->official_receipt), 404);

        return Storage::disk('local')->download($certificateRequest->official_receipt);
    }

    public function verify(Request $request, CertificateRequest $certificateRequest): RedirectResponse
    {
        $certificateRequest->update([
            'status' => CertificateRequestStatus::Verified,
            'remarks' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Official Receipt verified successfully.');
    }

    public function approve(Request $request, CertificateRequest $certificateRequest): RedirectResponse
    {
        abort_unless($certificateRequest->status === CertificateRequestStatus::Verified, 422);

        $certificateRequest->update([
            'status' => CertificateRequestStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Request approved and certificate PDF generated.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest): RedirectResponse
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

        return redirect()
            ->route('admin.official-receipts.show', $certificateRequest)
            ->with('status', 'Official Receipt rejected and student notified.');
    }
}
