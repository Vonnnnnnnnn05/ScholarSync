<?php

namespace App\Services;

use App\Enums\CertificateRequestStatus;
use App\Models\Certificate;
use App\Models\CertificateRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CertificateGenerationService
{
    public function generateForRequest(CertificateRequest $certificateRequest, ?User $generatedBy = null): Certificate
    {
        if ($certificateRequest->status !== CertificateRequestStatus::Approved) {
            throw new RuntimeException('Only approved certificate requests can generate certificates.');
        }

        return DB::transaction(function () use ($certificateRequest, $generatedBy): Certificate {
            $certificateRequest->loadMissing(['student.user', 'certificate']);

            if ($certificateRequest->certificate) {
                return $certificateRequest->certificate;
            }

            $certificateNumber = $this->nextCertificateNumber();
            $filePath = 'certificates/generated/'.Str::slug($certificateNumber).'.pdf';

            $pdf = Pdf::loadView('certificates.pdf.no-scholarship', [
                'certificateRequest' => $certificateRequest,
                'student' => $certificateRequest->student,
                'certificateNumber' => $certificateNumber,
                'issuedAt' => now(),
            ])->setPaper('letter', 'portrait');

            Storage::disk('local')->put($filePath, $pdf->output());

            return Certificate::create([
                'certificate_request_id' => $certificateRequest->id,
                'certificate_number' => $certificateNumber,
                'file_path' => $filePath,
                'generated_by' => $generatedBy?->id ?? $certificateRequest->approved_by,
                'generated_at' => now(),
            ]);
        });
    }

    private function nextCertificateNumber(): string
    {
        $prefix = 'CERT-'.now()->format('Y').'-';

        $lastCertificate = Certificate::query()
            ->where('certificate_number', 'like', $prefix.'%')
            ->orderByDesc('certificate_number')
            ->first();

        $nextSequence = $lastCertificate
            ? ((int) Str::afterLast($lastCertificate->certificate_number, '-')) + 1
            : 1;

        do {
            $certificateNumber = $prefix.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
            $nextSequence++;
        } while (Certificate::where('certificate_number', $certificateNumber)->exists());

        return $certificateNumber;
    }
}
