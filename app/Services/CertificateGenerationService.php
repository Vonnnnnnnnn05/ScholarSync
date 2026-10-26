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
        $certificateRequest->loadMissing('student');

        if ($certificateRequest->student?->is_scholarship_grantee) {
            throw new RuntimeException('This student is currently an active scholarship grantee and cannot receive a Certificate of Non-Grantee.');
        }

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
            $issuedAt = now();
            [$semester, $academicYear] = $this->academicPeriod($issuedAt);
            $approver = $generatedBy ?? ($certificateRequest->approved_by ? User::find($certificateRequest->approved_by) : null);
            $signatureImage = $approver?->signatureDataUri();
            $signaturePath = $approver?->signature_path;

            $pdf = Pdf::loadView('certificates.pdf.no-scholarship', [
                'certificateRequest' => $certificateRequest,
                'student' => $certificateRequest->student,
                'certificateNumber' => $certificateNumber,
                'issuedAt' => $issuedAt,
                'semester' => $semester,
                'academicYear' => $academicYear,
                'signatureImage' => $signatureImage,
            ])->setPaper('a4', 'portrait');

            Storage::disk('local')->put($filePath, $pdf->output());

            $certificate = Certificate::create([
                'certificate_request_id' => $certificateRequest->id,
                'certificate_number' => $certificateNumber,
                'file_path' => $filePath,
                'signature_path' => $signaturePath,
                'generated_by' => $generatedBy?->id ?? $certificateRequest->approved_by,
                'generated_at' => now(),
            ]);

            app(AuditTrailService::class)->record('certificate_generated', $certificate, [
                'certificate_number' => $certificateNumber,
                'certificate_request_id' => $certificateRequest->id,
            ]);

            return $certificate;
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

    /** @return array{0: string, 1: string} */
    public function academicPeriod(\DateTimeInterface $issuedAt): array
    {
        $month = (int) $issuedAt->format('n');
        $year = (int) $issuedAt->format('Y');

        if ($month >= 8) {
            return ['First', $year.'-'.($year + 1)];
        }

        if ($month <= 5) {
            return ['Second', ($year - 1).'-'.$year];
        }

        return ['Midyear', ($year - 1).'-'.$year];
    }

    public function previewPdfForRequest(CertificateRequest $certificateRequest, ?User $viewer = null): \Barryvdh\DomPDF\PDF
    {
        $certificateRequest->loadMissing(['student.user', 'certificate']);

        $issuedAt = now();
        [$semester, $academicYear] = $this->academicPeriod($issuedAt);
        $approver = $viewer ?? ($certificateRequest->approved_by ? User::find($certificateRequest->approved_by) : null);
        $signatureImage = $approver?->signatureDataUri();

        return Pdf::loadView('certificates.pdf.no-scholarship', [
            'certificateRequest' => $certificateRequest,
            'student' => $certificateRequest->student,
            'certificateNumber' => 'CERT-'.$issuedAt->format('Y').'-PREVIEW',
            'issuedAt' => $issuedAt,
            'semester' => $semester,
            'academicYear' => $academicYear,
            'signatureImage' => $signatureImage,
            'isPreview' => true,
        ])->setPaper('a4', 'portrait');
    }
}

