<?php

namespace App\Services;

use App\Models\ScholarshipMasterlist;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifiedMasterlistExportService
{
    public function download(ScholarshipMasterlist $masterlist): StreamedResponse
    {
        $masterlist->load(['agency', 'records.campus']);

        return response()->streamDownload(function () use ($masterlist): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['scholarship_agency', 'source_file', 'student_name', 'campus']);
            foreach ($masterlist->records->where('verification_status', 'verified') as $record) {
                fputcsv($handle, [$masterlist->agency->agency_name, $masterlist->file_name, $record->student_name, $record->campus?->name]);
            }
            fclose($handle);
        }, 'verified-beneficiaries-'.$masterlist->id.'.csv', ['Content-Type' => 'text/csv']);
    }
}
