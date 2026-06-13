<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * @param  array{title: string, headings: array<int, string>, rows: Collection<int, array<int, mixed>>}  $reportData
     * @param  array<string, mixed>  $filters
     */
    public function export(string $type, string $format, array $reportData, array $filters = []): Response|StreamedResponse
    {
        $fileName = str($type)->slug().'-'.now()->format('Ymd-His').'.'.$this->extension($format);
        $path = 'reports/'.$fileName;

        return match ($format) {
            'pdf' => $this->pdf($type, $path, $reportData, $filters),
            'excel' => $this->excel($type, $path, $reportData, $filters),
            default => $this->csv($type, $path, $reportData, $filters),
        };
    }

    private function pdf(string $type, string $path, array $reportData, array $filters): Response
    {
        $content = Pdf::loadView('reports.table', [
            'report' => $reportData,
            'filters' => $filters,
        ])->output();

        Storage::disk('local')->put($path, $content);
        $this->record($type, 'pdf', $path, $filters);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.basename($path).'"',
        ]);
    }

    private function excel(string $type, string $path, array $reportData, array $filters): Response
    {
        $content = view('reports.excel', ['report' => $reportData])->render();

        Storage::disk('local')->put($path, $content);
        $this->record($type, 'excel', $path, $filters);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.basename($path).'"',
        ]);
    }

    private function csv(string $type, string $path, array $reportData, array $filters): StreamedResponse
    {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $reportData['headings']);

        foreach ($reportData['rows'] as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        Storage::disk('local')->put($path, $content);
        $this->record($type, 'csv', $path, $filters);

        return response()->streamDownload(fn () => print $content, basename($path), [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function extension(string $format): string
    {
        return $format === 'excel' ? 'xls' : $format;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function record(string $type, string $format, string $path, array $filters): void
    {
        Report::create([
            'report_type' => $type,
            'format' => $format,
            'generated_by' => auth()->id(),
            'file_path' => $path,
            'filters' => $filters,
            'generated_at' => now(),
        ]);

        app(AuditTrailService::class)->record('report_generated', null, [
            'report_type' => $type,
            'format' => $format,
            'file_path' => $path,
        ]);
    }
}
