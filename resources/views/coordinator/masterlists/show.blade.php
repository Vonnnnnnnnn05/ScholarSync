<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $masterlist->agency->agency_name }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Validate :file', ['file' => $masterlist->file_name]) }}
                </h2>
            </div>
            <a href="{{ route('coordinator.masterlists.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Back to Queue') }}
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

            @if ($errors->has('submit'))
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
                    {{ $errors->first('submit') }}
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
                    <p class="mt-2 text-lg font-semibold text-emerald-800">{{ Str::headline($masterlist->status) }}</p>
                </div>
            </div>

            <div class="mb-5 flex flex-wrap gap-2">
                <a href="{{ route('coordinator.masterlists.show', $masterlist) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === '' ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">
                    {{ __('All') }}
                </a>
                @foreach ($verificationStatuses as $status)
                    <a href="{{ route('coordinator.masterlists.show', [$masterlist, 'status' => $status]) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === $status ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">
                        {{ Str::headline($status) }}
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Scholarship') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('System Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Coordinator Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($records as $record)
                                @php
                                    $verificationClass = match ($record->verification_status) {
                                        'enrolled' => 'bg-emerald-100 text-emerald-900',
                                        'unenrolled' => 'bg-blue-100 text-blue-900',
                                        'duplicate' => 'bg-yellow-100 text-yellow-900',
                                        'invalid' => 'bg-red-100 text-red-900',
                                        default => 'bg-gray-100 text-gray-900',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <div class="font-semibold text-gray-950">{{ $record->student_name ?: __('Missing') }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->student_id_number ?: __('No student ID') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        <div>{{ $record->scholarship_program ?: __('Missing program') }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->fund_source ?: __('Missing fund source') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $verificationClass }}">
                                            {{ Str::headline($record->verification_status) }}
                                        </span>
                                        <div class="mt-2 max-w-xs text-xs text-gray-600">{{ $record->remarks ?: __('No remarks') }}</div>
                                    </td>
                                    <td class="min-w-80 px-6 py-4 text-sm">
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('coordinator.masterlists.records.update', [$masterlist, $record]) }}" class="space-y-3">
                                                @csrf
                                                @method('PATCH')

                                                <select name="coordinator_status" class="block w-full rounded-md border-emerald-900/20 bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                                    <option value="validated" @selected($record->coordinator_status === 'validated')>{{ __('Validate') }}</option>
                                                    <option value="for_correction" @selected($record->coordinator_status === 'for_correction')>{{ __('For Correction') }}</option>
                                                    <option value="rejected" @selected($record->coordinator_status === 'rejected')>{{ __('Reject') }}</option>
                                                    <option value="for_chairman_review" @selected($record->coordinator_status === 'for_chairman_review')>{{ __('For Chairman Review') }}</option>
                                                </select>

                                                <textarea name="remarks" rows="2" class="block w-full rounded-md border-emerald-900/20 bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" placeholder="{{ __('Remarks') }}">{{ old('remarks', $record->remarks) }}</textarea>

                                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                                                    {{ __('Save') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-900">
                                                {{ Str::headline($record->coordinator_status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No records found for this filter.') }}
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

            @if ($canEdit)
                <form method="POST" action="{{ route('coordinator.masterlists.submit', $masterlist) }}" class="mt-6 flex justify-end">
                    @csrf
                    <x-confirm-submit message="Submit this validated masterlist to the chairman?" class="min-h-11 px-4">
                        {{ __('Submit to Chairman') }}
                    </x-confirm-submit>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
