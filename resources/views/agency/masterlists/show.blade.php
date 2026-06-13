<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $agency->agency_name }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ $masterlist->file_name }}
                </h2>
            </div>
            <a href="{{ route('agency.masterlists.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Back to Masterlists') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Records') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ $masterlist->total_records }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Enrolled') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ $masterlist->enrolled_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Unenrolled') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-blue-800">{{ $masterlist->unenrolled_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Duplicates') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-yellow-800">{{ $masterlist->duplicate_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Invalid') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-red-700">{{ $masterlist->invalid_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Status') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ Str::headline($masterlist->status) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student ID') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Fund Source') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Verification') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Final Result') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($records as $record)
                                @php
                                    $statusClass = match ($record->verification_status) {
                                        'enrolled' => 'bg-emerald-100 text-emerald-900',
                                        'unenrolled' => 'bg-blue-100 text-blue-900',
                                        'duplicate' => 'bg-yellow-100 text-yellow-900',
                                        'invalid' => 'bg-red-100 text-red-900',
                                        default => 'bg-gray-100 text-gray-900',
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800">{{ $record->student_id_number ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $record->student_name ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $record->scholarship_program ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $record->fund_source ?: __('Missing') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ Str::headline($record->verification_status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @php
                                            $chairmanClass = match ($record->chairman_status) {
                                                'approved' => 'bg-emerald-100 text-emerald-900',
                                                'rejected' => 'bg-red-100 text-red-900',
                                                default => 'bg-gray-100 text-gray-900',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $chairmanClass }}">
                                            {{ Str::headline($record->chairman_status) }}
                                        </span>
                                    </td>
                                    <td class="max-w-sm px-6 py-4 text-sm text-gray-700">{{ $record->remarks ?: __('No remarks') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No records found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
