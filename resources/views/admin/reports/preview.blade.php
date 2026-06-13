<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Reports Module') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $report['title'] }}</h2>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Back to Reports') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
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
            </section>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach ($report['headings'] as $heading)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($report['rows'] as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($report['headings']) }}" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No records found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
