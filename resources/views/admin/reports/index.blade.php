<x-app-layout>
    @php
        $reportDiagnosticDetails = [
            'scholar_information' => [
                'badge' => 'Scholars',
                'problems' => __('Identifies inactive scholars, unregistered student ID numbers, campus distribution gaps, and incomplete student academic records.'),
            ],
            'certificate_requests' => [
                'badge' => 'Certificates',
                'problems' => __('Identifies pending approvals, processing delays, rejected certificate requests, and unserved student applications.'),
            ],
            'or_verification' => [
                'badge' => 'Receipts',
                'problems' => __('Flags fraudulent or blurry receipts, rejected payment submissions, unverified requests, and compliance audit remarks.'),
            ],
            'masterlists' => [
                'badge' => 'Masterlists',
                'problems' => __('Pinpoints masterlist upload errors, invalid entries, duplicate students, unenrolled beneficiaries, and qualification tallies.'),
            ],
            'scholarship_agencies' => [
                'badge' => 'Agencies',
                'problems' => __('Highlights inactive partner agencies, missing focal person contacts, expired partnerships, and agencies without active scholarship policies.'),
            ],
            'approved_rejected' => [
                'badge' => 'Audit Log',
                'problems' => __('Explicitly lists all rejected transactions across certificates and masterlists, with rejection causes and administrative remarks.'),
            ],
            'enrollment_verification' => [
                'badge' => 'Verification',
                'problems' => __('Exposes unenrolled scholars, missing Certificate of Registration (No COR Printed), and students failing scholarship criteria.'),
            ],
            'agency_submissions' => [
                'badge' => 'Submissions',
                'problems' => __('Detects unpublished agency guidelines, draft or unapproved policies, and submission discrepancies across programs.'),
            ],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Administrator') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">{{ __('Reports Module') }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                    {{ count($types) }} {{ __('Available Reports') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
            
            {{-- Top Row: Custom Filter Form & Recent Exports --}}
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Generate Filtered Report') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Apply custom filters by date, status, student, or agency.') }}</p>
                    </div>
                    <form method="GET" action="{{ route('admin.reports.preview') }}" class="mt-5 space-y-4">
                        @include('admin.reports.partials.form')
                        <div class="pt-2">
                            <x-primary-button class="w-full justify-center">{{ __('Generate Report') }}</x-primary-button>
                        </div>
                    </form>
                </section>

                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-950">{{ __('Recent Exports') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('Latest generated reports with one-click print access.') }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Report') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Format') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Generated') }}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($recentReports as $report)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ Str::headline($report->report_type) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium uppercase {{ $report->format === 'pdf' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' : ($report->format === 'excel' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10' : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/10') }}">
                                                {{ strtoupper($report->format) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ optional($report->generated_at)->format('M d, Y h:i A') }}</td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <a href="{{ route('admin.reports.preview', array_merge(['type' => $report->report_type, 'format' => $report->format, 'print' => 1], $report->filters ?? [])) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-md border border-emerald-700/30 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                                                <svg class="h-3.5 w-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                <span>{{ __('Print Report') }}</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No reports generated yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            {{-- Main Reports Table: One Row per Report with Diagnostic Scope and Exactly 1 Action Button --}}
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-950">{{ __('University Reports Table') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('Easily identify discrepancies, status issues, and review diagnostics across all university reports.') }}</p>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ __('Click "Print Report" on any report to view data and print.') }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Report Name') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Problems & Issues Identified') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Category') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($types as $typeKey => $typeName)
                                @php
                                    $diag = $reportDiagnosticDetails[$typeKey] ?? [
                                        'badge' => 'General',
                                        'problems' => __('Identifies records and anomalies matching the specified filter criteria.'),
                                    ];
                                @endphp
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-bold text-gray-950">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                            <span>{{ $typeName }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-md">
                                        <p class="text-xs leading-relaxed text-gray-700">{{ $diag['problems'] }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
                                            {{ $diag['badge'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        {{-- Exactly 1 button per report as requested --}}
                                        <a href="{{ route('admin.reports.preview', ['type' => $typeKey, 'format' => 'pdf', 'print' => 1]) }}" target="_blank" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-emerald-800 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            <span>{{ __('Print Report') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
