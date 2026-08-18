<?php

namespace App\Services;

use App\Models\ScholarshipMasterlist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifiedMasterlistExportService
{
    public function download(ScholarshipMasterlist $masterlist): StreamedResponse
    {
        $masterlist->load(['records.campus']);

        return response()->streamDownload(function () use ($masterlist): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['source_file', 'student_id_number', 'student_name', 'campus', 'enrollment_status', 'cor_status', 'qualification_status', 'verified_at']);
            foreach ($masterlist->records->where('final_qualification_status', 'qualified') as $record) {
                fputcsv($handle, [
                    $masterlist->file_name, $record->student_id_number,
                    $record->student_name, $record->campus?->name, $record->final_enrollment_status,
                    $record->final_cor_status, $record->final_qualification_status, optional($record->automatic_verified_at)->toIso8601String(),
                ]);
            }
            fclose($handle);
        }, 'verified-beneficiaries-'.$masterlist->id.'.csv', ['Content-Type' => 'text/csv']);
    }
}
