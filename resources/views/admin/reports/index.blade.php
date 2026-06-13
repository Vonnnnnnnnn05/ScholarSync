<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Administrator') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Reports Module') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Generate Report') }}</h3>
                    <form method="GET" action="{{ route('admin.reports.preview') }}" class="mt-5 space-y-4">
                        @include('admin.reports.partials.form')
                        <x-primary-button>{{ __('Preview Report') }}</x-primary-button>
                    </form>
                </section>

                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Recent Exports') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Report') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Format') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Generated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($recentReports as $report)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ Str::headline($report->report_type) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ strtoupper($report->format) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ optional($report->generated_at)->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No reports generated yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
