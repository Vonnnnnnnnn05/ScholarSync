<x-app-layout>
    @php
        $isWide = count($report['headings']) >= 7 || $selectedType === 'masterlists';

        // Problem / issue detection logic to help administrators identify anomalies freely
        $problemKeywords = [
            'rejected', 'not enrolled', 'unenrolled', 'no cor', 'no cor printed',
            'not qualified', 'unqualified', 'invalid', 'needs review', 'unmatched',
            'ambiguous', 'inconsistent', 'missing', 'disapproved'
        ];

        $problemRowCount = 0;
        foreach ($report['rows'] as $row) {
            foreach ($row as $cell) {
                $lower = strtolower(trim((string)$cell));
                foreach ($problemKeywords as $keyword) {
                    if (str_contains($lower, $keyword)) {
                        $problemRowCount++;
                        break 2;
                    }
                }
            }
        }
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-700">
                    <a href="{{ route('admin.reports.index') }}" class="hover:underline">{{ __('Reports Module') }}</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-500">{{ $report['title'] }}</span>
                </div>
                <h2 class="mt-1 text-xl font-bold leading-tight text-gray-900">{{ $report['title'] }}</h2>
            </div>
            <div class="flex items-center gap-3">
                {{-- Only 1 Print Report button in header --}}
                <button type="button" onclick="window.print()" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>{{ __('Print Report') }}</span>
                </button>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                    {{ __('Back to Reports') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10 print:p-0">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 print:max-w-none print:p-0">
            
            {{-- Export Panel (Hidden on Print) --}}
            <section class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 print:hidden">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Export Controls & Data Overview') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Download file copies or review highlighted diagnostic problems.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                            {{ count($report['rows']) }} {{ __('Total Records') }}
                        </span>
                        @if ($problemRowCount > 0)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-600/30">
                                {{ $problemRowCount }} {{ __('Issues / Discrepancies') }}
                            </span>
                        @endif
                        @if ($isWide)
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-800 ring-1 ring-inset ring-blue-600/20">
                                {{ __('Wide Table (Landscape Print)') }}
                            </span>
                        @endif
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.export') }}" class="grid gap-4 lg:grid-cols-6">
                    <input type="hidden" name="type" value="{{ $selectedType }}">
                    @foreach ($filters as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <div class="lg:col-span-2">
                        <x-input-label for="format" :value="__('Export Format')" />
                        <select id="format" name="format" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            @foreach ($formats as $value => $label)
                                <option value="{{ $value }}" @selected($selectedFormat === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end lg:col-span-4">
                        <x-primary-button>{{ __('Export Report') }}</x-primary-button>
                    </div>
                </form>

                @if ($filters !== [])
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 text-xs text-gray-600">
                        <span class="font-semibold text-gray-700">{{ __('Active Filters:') }}</span>
                        @foreach ($filters as $key => $value)
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                {{ Str::headline($key) }}: <strong class="ml-1 text-gray-900">{{ $value }}</strong>
                            </span>
                        @endforeach
                        <a href="{{ route('admin.reports.preview', ['type' => $selectedType, 'format' => $selectedFormat]) }}" class="ml-auto text-emerald-700 underline hover:text-emerald-900">
                            {{ __('Clear filters') }}
                        </a>
                    </div>
                @endif
            </section>

            {{-- Problem / Discrepancy Diagnostics Alert (Helps identify problems freely) --}}
            @if ($problemRowCount > 0)
                <div class="mb-4 flex items-center justify-between rounded-lg border border-amber-300 bg-amber-50 p-4 text-xs text-amber-900 print:hidden">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-amber-200 p-1.5 text-amber-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-amber-950">
                                {{ $problemRowCount }} {{ __('Discrepancies / Attention Items Detected') }}
                            </p>
                            <p class="text-amber-800 mt-0.5">
                                {{ __('Records requiring review or flagged with issues (such as No COR Printed, Unenrolled, Rejected, or Needs Review) are clearly highlighted below.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Printable Report Container --}}
            <div class="printable-report-area overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 print:ring-0 print:shadow-none print:rounded-none">
                
                {{-- Official University Letterhead for Print (Visible only in Print Mode) --}}
                <div class="hidden print:block print-header p-2 mb-4 border-b-2 border-gray-900 pb-4">
                    <div class="text-center">
                        <p class="text-[11px] tracking-widest text-gray-700 uppercase">Republic of the Philippines</p>
                        <h1 class="text-lg font-bold uppercase tracking-wider text-gray-950 font-serif">SULTAN KUDARAT STATE UNIVERSITY</h1>
                        <p class="text-[10px] text-gray-600">Province of Sultan Kudarat, 9800 Philippines</p>
                        <p class="text-[11px] font-semibold text-gray-800 uppercase mt-1">OFFICE OF STUDENT AFFAIRS AND SERVICES • SCHOLARSHIP UNIT</p>
                        <p class="text-[10px] text-gray-500 italic">ScholarSync — Integrated Scholarship Management & Verification System</p>
                    </div>

                    <div class="mt-4 flex items-end justify-between border-t border-gray-300 pt-3 text-[11px]">
                        <div>
                            <h2 class="text-base font-bold uppercase text-gray-950">{{ $report['title'] }}</h2>
                            @if ($filters !== [])
                                <p class="text-gray-600 mt-0.5">
                                    <span class="font-semibold text-gray-800">{{ __('Filter(s):') }}</span>
                                    @foreach ($filters as $key => $value)
                                        {{ Str::headline($key) }}: {{ $value }}@if (! $loop->last) | @endif
                                    @endforeach
                                </p>
                            @else
                                <p class="text-gray-500 italic mt-0.5">{{ __('All Records (No filters applied)') }}</p>
                            @endif
                        </div>
                        <div class="text-right text-gray-600 space-y-0.5">
                            <p><span class="font-semibold text-gray-800">{{ __('Date Generated:') }}</span> {{ now()->format('M d, Y h:i A') }}</p>
                            <p><span class="font-semibold text-gray-800">{{ __('Generated By:') }}</span> {{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</p>
                            <p><span class="font-semibold text-gray-800">{{ __('Total Records:') }}</span> {{ count($report['rows']) }} @if($problemRowCount > 0) ({{ $problemRowCount }} with issues) @endif</p>
                        </div>
                    </div>
                </div>

                {{-- Report Data Table with Problem Identification --}}
                <div class="overflow-x-auto print:overflow-visible">
                    <table class="min-w-full divide-y divide-gray-200 print:divide-gray-400 print-table">
                        <thead class="bg-gray-50 print:bg-gray-100">
                            <tr>
                                @foreach ($report['headings'] as $heading)
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-gray-600 print:px-3 print:py-2 print:text-[10px] print:text-gray-900 print:border print:border-gray-400">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white print:divide-gray-300">
                            @forelse ($report['rows'] as $row)
                                @php
                                    $rowHasProblem = false;
                                    foreach ($row as $cell) {
                                        $lower = strtolower(trim((string)$cell));
                                        foreach ($problemKeywords as $keyword) {
                                            if (str_contains($lower, $keyword)) {
                                                $rowHasProblem = true;
                                                break 2;
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="{{ $rowHasProblem ? 'bg-amber-50/30 print:bg-transparent' : 'hover:bg-gray-50/75 print:hover:bg-transparent' }}">
                                    @foreach ($row as $value)
                                        @php
                                            $valLower = strtolower(trim((string)$value));
                                            $isProblem = false;
                                            $isPositive = false;

                                            foreach ($problemKeywords as $kw) {
                                                if (str_contains($valLower, $kw)) {
                                                    $isProblem = true;
                                                    break;
                                                }
                                            }

                                            if (!$isProblem && in_array($valLower, ['approved', 'enrolled', 'cor printed', 'cor_printed', 'qualified', 'verified', 'matched', 'published', 'active'])) {
                                                $isPositive = true;
                                            }
                                        @endphp
                                        <td class="px-6 py-4 text-sm text-gray-700 print:px-3 print:py-2 print:text-[10px] print:text-gray-900 print:border print:border-gray-300">
                                            @if ($isProblem)
                                                <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 ring-1 ring-inset ring-amber-600/20 print:bg-transparent print:ring-0 print:font-bold print:text-gray-950">
                                                    {{ $value }}
                                                </span>
                                            @elseif ($isPositive)
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20 print:bg-transparent print:ring-0 print:text-gray-900">
                                                    {{ $value }}
                                                </span>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($report['headings']) }}" class="px-6 py-12 text-center text-sm text-gray-600 print:py-6 print:border print:border-gray-300">
                                        {{ __('No records found matching the specified criteria.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Official Signatory Block for Print (Visible only in Print Mode) --}}
                <div class="hidden print:block print-signatures mt-10 pt-4">
                    <div class="grid grid-cols-3 gap-8 text-center text-[10px]">
                        <div>
                            <p class="text-gray-600">{{ __('Prepared by:') }}</p>
                            <div class="mt-8 border-b border-gray-900 mx-4"></div>
                            <p class="mt-1 font-bold text-gray-950 uppercase">{{ auth()->user()->name }}</p>
                            <p class="text-gray-600">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">{{ __('Verified by:') }}</p>
                            <div class="mt-8 border-b border-gray-900 mx-4"></div>
                            <p class="mt-1 font-bold text-gray-950 uppercase">{{ __('Scholarship Coordinator') }}</p>
                            <p class="text-gray-600">{{ __('University Scholarship Unit') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600">{{ __('Noted / Approved by:') }}</p>
                            <div class="mt-8 border-b border-gray-900 mx-4"></div>
                            <p class="mt-1 font-bold text-gray-950 uppercase">{{ __('Director / Campus Official') }}</p>
                            <p class="text-gray-600">{{ __('Office of Student Affairs and Services') }}</p>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-gray-300 pt-2 flex items-center justify-between text-[9px] text-gray-500">
                        <span>{{ __('ScholarSync • Sultan Kudarat State University • Official Document') }}</span>
                        <span>{{ __('Printed on: ') }} {{ now()->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Page Print Styles --}}
    <style>
        @media print {
            @page {
                size: {{ $isWide ? 'landscape' : 'portrait' }};
                margin: {{ $isWide ? '10mm 8mm' : '12mm 10mm' }};
            }

            html, body {
                background: #ffffff !important;
                color: #111827 !important;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            }

            #role-sidebar,
            aside,
            nav,
            header,
            .print-hide,
            .print\:hidden {
                display: none !important;
            }

            .printable-report-area {
                box-shadow: none !important;
                border: none !important;
            }

            table.print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9.5pt !important;
            }

            table.print-table thead {
                display: table-header-group !important;
            }

            table.print-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .print-signatures {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>

    @if (!empty($autoPrint))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 300);
            });
        </script>
    @endif
</x-app-layout>
