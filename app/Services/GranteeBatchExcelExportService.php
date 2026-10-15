<?php

namespace App\Services;

use App\Models\GranteeUploadBatch;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GranteeBatchExcelExportService
{
    public function export(GranteeUploadBatch $batch): StreamedResponse
    {
        $batch->load([
            'campus',
            'uploader',
            'deliveredToCoordinatorBy',
            'sentToRegistrarBy',
            'registrarVerifiedBy',
            'coordinatorEndorsedBy',
            'stagedRecords.matchedSisStudent',
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Verified Grantees');

        // Document Title
        $sheet->setCellValue('A1', 'SCHOLARSYNC - OFFICIAL GRANTEE VERIFICATION ROSTER');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('064E3B'));

        $campusName = $batch->campus?->name ?: 'All Campuses';
        $agencyName = $batch->agency_name ?: 'External Scholarship Agency';
        $sheet->setCellValue('A2', "Batch #{$batch->id} | Agency: {$agencyName} | Campus: {$campusName} | Academic Year: {$batch->academic_year} {$batch->semester}");
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('374151'));

        $registrarInfo = $batch->registrar_verified_at
            ? "Registrar Verified: Yes ({$batch->registrarVerifiedBy?->name} on " . $batch->registrar_verified_at->format('M d, Y h:i A') . ")"
            : "Registrar Verification: Pending";
        $coordinatorInfo = $batch->coordinator_endorsed_at
            ? "Coordinator Endorsed: Yes ({$batch->coordinatorEndorsedBy?->name} on " . $batch->coordinator_endorsed_at->format('M d, Y h:i A') . ")"
            : "Coordinator Endorsement: Pending";

        $sheet->setCellValue('A3', "Status: " . strtoupper(str_replace('_', ' ', $batch->status)) . " | {$registrarInfo} | {$coordinatorInfo}");
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->getFont()->setSize(9)->setColor(new Color('4B5563'));

        if ($batch->registrar_notes) {
            $sheet->setCellValue('A4', "Registrar Notes: " . $batch->registrar_notes);
            $sheet->mergeCells('A4:M4');
            $sheet->getStyle('A4')->getFont()->setSize(9)->setItalic(true)->setColor(new Color('065F46'));
            $headerRow = 6;
        } else {
            $headerRow = 5;
        }

        // Table Column Headers
        $headers = [
            'A' => '#',
            'B' => 'Student ID',
            'C' => 'Student Name',
            'D' => 'Scholarship Category',
            'E' => 'Campus Destination',
            'F' => 'Matched SIS Student ID',
            'G' => 'Course / Program',
            'H' => 'Year Level',
            'I' => 'Enrollment Status',
            'J' => 'COR Printed',
            'K' => 'Verification Status',
            'L' => 'Remarks',
            'M' => 'Remarks Details / Reason',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$headerRow}", $title);
        }

        $headerRange = "A{$headerRow}:M{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '065F46'], // Emerald 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '047857'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        $rowNum = $headerRow + 1;
        $records = $batch->stagedRecords->sortBy('row_number');
        foreach ($records as $record) {
            $sis = $record->matchedSisStudent;

            // Determine enrollment and COR status from matched SIS record or staged record
            $isEnrolled = false;
            $hasCorPrinted = false;

            if ($sis) {
                $rawStatus = strtolower(trim((string) $sis->enrollment_status));
                $isEnrolled = in_array($rawStatus, ['enrolled', 'regular', 'active'], true);
                $hasCorPrinted = (bool) $sis->cor_printed;
            } elseif ($record->enrollment_status) {
                $rawStatus = strtolower(trim((string) $record->enrollment_status));
                $isEnrolled = in_array($rawStatus, ['enrolled', 'regular', 'active'], true);
                $hasCorPrinted = !in_array($rawStatus, ['no_cor_printed', 'no_cor'], true);
            }

            // Honor official green verification if manually confirmed
            if ($record->verification_status === 'GREEN_VERIFIED') {
                $isEnrolled = true;
                $hasCorPrinted = true;
            }

            // Remarks determination per user requirements:
            // - If enrolled AND COR printed => Eligible
            // - If not enrolled => Not Eligible
            // - If No COR Printed => Not Eligible
            if ($isEnrolled && $hasCorPrinted) {
                $remarks = 'Eligible';
                $remarksDetail = 'Enrolled with Printed COR';
            } elseif ($isEnrolled && !$hasCorPrinted) {
                $remarks = 'Not Eligible';
                $remarksDetail = 'No COR Printed';
            } elseif ($sis && !$isEnrolled) {
                $remarks = 'Not Eligible';
                $remarksDetail = 'Not Enrolled in SIS (' . ucfirst($sis->enrollment_status) . ')';
            } else {
                $remarks = 'Not Eligible';
                $remarksDetail = $record->conflict_reason ?: 'Student Not Found in Campus Repository';
            }

            if ($record->resolution_notes) {
                $remarksDetail .= ' | Resolution: ' . $record->resolution_notes;
            }

            $verificationLabel = match ($record->verification_status) {
                'GREEN_VERIFIED' => 'Verified (Enrolled + COR)',
                'AMBER_CONFLICT' => 'Needs Review (No COR)',
                'RED_UNMATCHED' => 'Unmatched / Inactive',
                default => 'Pending Verification',
            };

            $sheet->setCellValue("A{$rowNum}", $record->row_number);
            $sheet->setCellValueExplicit("B{$rowNum}", (string) ($record->student_id ?: $sis?->student_id_number ?: '—'), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowNum}", $record->student_name);
            $sheet->setCellValue("D{$rowNum}", $record->scholarship_program ?: 'General Grantee');
            $sheet->setCellValue("E{$rowNum}", $record->campus ?: $batch->campus?->name ?: '—');
            $sheet->setCellValueExplicit("F{$rowNum}", (string) ($sis?->student_id_number ?: '—'), DataType::TYPE_STRING);
            $sheet->setCellValue("G{$rowNum}", $record->program ?: $sis?->course ?: '—');
            $sheet->setCellValue("H{$rowNum}", $sis?->year_level ? "Year {$sis->year_level}" : '—');
            $sheet->setCellValue("I{$rowNum}", $sis?->enrollment_status ? ucfirst($sis->enrollment_status) : ($isEnrolled ? 'Enrolled' : ($sis ? 'Unenrolled' : '—')));
            $sheet->setCellValue("J{$rowNum}", $sis ? ($sis->cor_printed ? 'Printed' : 'Not Printed') : ($hasCorPrinted ? 'Printed' : 'Not Printed'));
            $sheet->setCellValue("K{$rowNum}", $verificationLabel);
            $sheet->setCellValue("L{$rowNum}", $remarks);
            $sheet->setCellValue("M{$rowNum}", $remarksDetail);

            // Row zebra striping
            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }

            // Align statuses and remarks
            $sheet->getStyle("I{$rowNum}:L{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Highlight verification status column
            $statusCell = "K{$rowNum}";
            if ($record->verification_status === 'GREEN_VERIFIED') {
                $sheet->getStyle($statusCell)->getFont()->setBold(true)->getColor()->setRGB('047857');
            } elseif ($record->verification_status === 'AMBER_CONFLICT') {
                $sheet->getStyle($statusCell)->getFont()->setBold(true)->getColor()->setRGB('B45309');
            } elseif ($record->verification_status === 'RED_UNMATCHED') {
                $sheet->getStyle($statusCell)->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
            }

            // Highlight Remarks column (Column L)
            $remarksCell = "L{$rowNum}";
            if ($remarks === 'Eligible') {
                $sheet->getStyle($remarksCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '047857'], // Emerald 700
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECFDF5'], // Soft Emerald
                    ],
                ]);
            } else {
                $sheet->getStyle($remarksCell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'B91C1C'], // Rose 700
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF2F2'], // Soft Rose
                    ],
                ]);
            }

            $rowNum++;
        }

        // Auto-fit all columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $cleanAgency = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batch->agency_name ?: 'agency');
        $cleanCampus = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batch->campus?->code ?: 'campus');
        $fileName = "ScholarSync_Batch{$batch->id}_{$cleanAgency}_{$cleanCampus}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
