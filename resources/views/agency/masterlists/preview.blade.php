<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $agency->agency_name }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Preview Masterlist') }}
                </h2>
            </div>
            <a href="{{ route('agency.masterlists.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Choose Another File') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Total Records') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ $preview['total_records'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Duplicate IDs') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-yellow-800">{{ $preview['duplicate_count'] }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Invalid Rows') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-red-700">{{ $preview['invalid_count'] }}</p>
                </div>
            </div>

            @if ($preview['missing_columns'] !== [])
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                    {{ __('Missing required columns: :columns', ['columns' => implode(', ', $preview['missing_columns'])]) }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Row') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student ID') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Fund Source') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Review') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($preview['rows'] as $row)
                                <tr class="{{ $row['is_invalid'] ? 'bg-red-50' : ($row['is_duplicate'] ? 'bg-yellow-50' : '') }}">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-950">{{ $row['row_number'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-800">{{ $row['student_id_number'] ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $row['student_name'] ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $row['scholarship_program'] ?: __('Missing') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $row['fund_source'] ?: __('Missing') }}</td>
                                    <td class="max-w-sm px-6 py-4 text-sm text-gray-700">
                                        @if ($row['errors'] === [])
                                            <span class="inline-flex rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900">{{ __('Ready') }}</span>
                                        @else
                                            {{ implode(' ', $row['errors']) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No records found in the CSV file.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <form method="POST" action="{{ route('agency.masterlists.store') }}" class="mt-6 flex justify-end">
                @csrf
                <x-primary-button>
                    {{ __('Import Masterlist') }}
                </x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
