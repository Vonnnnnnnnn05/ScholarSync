<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Central Monitoring') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Scholar Records Monitoring') }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 flex flex-wrap gap-2">
                <a href="{{ route('admin.monitoring.scholars.index') }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === '' ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">{{ __('All') }}</a>
                @foreach (['approved', 'rejected', 'pending'] as $status)
                    <a href="{{ route('admin.monitoring.scholars.index', ['status' => $status]) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === $status ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">{{ Str::headline($status) }}</a>
                @endforeach
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Scholar') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Fund Source') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Agency') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($records as $record)
                                <tr>
                                    <td class="px-6 py-4 text-sm"><div class="font-semibold text-gray-950">{{ $record->student_name ?: __('Missing') }}</div><div class="text-xs text-gray-500">{{ $record->student_id_number ?: __('No student ID') }}</div></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $record->scholarship_program ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $record->fund_source ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $record->masterlist?->agency?->agency_name ?: __('No agency') }}</td>
                                    <td class="px-6 py-4 text-sm"><span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-900">{{ Str::headline($record->chairman_status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No scholar records found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($records->hasPages())<div class="border-t border-gray-200 px-6 py-4">{{ $records->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
