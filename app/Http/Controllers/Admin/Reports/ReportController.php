<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportBuilderService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportBuilderService $reports): View
    {
        return view('admin.reports.index', [
            'types' => $reports->types(),
            'formats' => ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'],
            'recentReports' => Report::query()->latest()->limit(10)->get(),
        ]);
    }

    public function preview(Request $request, ReportBuilderService $reports): View
    {
        $validated = $this->validated($request, $reports);

        return view('admin.reports.preview', [
            'types' => $reports->types(),
            'formats' => ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'],
            'selectedType' => $validated['type'],
            'selectedFormat' => $validated['format'],
            'filters' => $this->filters($validated),
            'report' => $reports->build($validated['type'], $this->filters($validated)),
        ]);
    }

    public function export(
        Request $request,
        ReportBuilderService $reports,
        ReportExportService $exporter,
    ): Response|StreamedResponse {
        $validated = $this->validated($request, $reports);
        $filters = $this->filters($validated);
        $report = $reports->build($validated['type'], $filters);

        return $exporter->export($validated['type'], $validated['format'], $report, $filters);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ReportBuilderService $reports): array
    {
        return $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys($reports->types()))],
            'format' => ['required', 'string', 'in:pdf,excel,csv'],
            'status' => ['nullable', 'string', 'max:255'],
            'student' => ['nullable', 'string', 'max:255'],
            'fund_source' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function filters(array $validated): array
    {
        return collect($validated)
            ->except(['type', 'format'])
            ->filter(fn ($value) => filled($value))
            ->all();
    }
}
