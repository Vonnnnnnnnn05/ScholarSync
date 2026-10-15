<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ $masterlist->program_name }}
                </h2>
                <p class="text-xs font-mono text-gray-500">{{ $masterlist->file_name }}</p>
            </div>
            <a href="{{ route('chairman.uploads.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
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

            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Records') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">{{ $masterlist->total_records }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Enrolled') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ $masterlist->enrolled_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('No COR Printed') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $masterlist->no_cor_printed_count }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-gray-600">{{ __('Unenrolled') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-blue-800">{{ $masterlist->unenrolled_count }}</p>
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
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Campus') }}</th>
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
                                        'no_cor_printed' => 'bg-amber-100 text-amber-900',
                                        'unenrolled' => 'bg-blue-100 text-blue-900',
                                        default => 'bg-gray-100 text-gray-900',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-semibold text-gray-950">{{ $record->student_name ?: __('Missing') }}</div>
                                        @if($record->student_id_number)
                                            <div class="text-xs font-mono text-gray-500">{{ $record->student_id_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $record->campus?->name ?: __('Needs manual assignment') }}</td>
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
