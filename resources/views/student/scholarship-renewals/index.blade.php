<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Continuing Scholarship') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Renewal Applications') }}</h2>
            </div>
            <a href="{{ route('student.scholarship-renewals.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                {{ __('Upload Requirements') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Documents') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Remarks') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($applications as $application)
                                @php
                                    $statusClass = match ($application->status) {
                                        \App\Enums\ScholarshipApplicationStatus::Approved => 'bg-emerald-100 text-emerald-900',
                                        \App\Enums\ScholarshipApplicationStatus::Rejected => 'bg-red-100 text-red-900',
                                        \App\Enums\ScholarshipApplicationStatus::NeedRevision => 'bg-yellow-100 text-yellow-900',
                                        \App\Enums\ScholarshipApplicationStatus::UnderEvaluation => 'bg-blue-100 text-blue-900',
                                        default => 'bg-gray-100 text-gray-900',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">
                                        {{ $application->scholarship_program }}
                                        <div class="text-xs font-normal text-gray-500">{{ $application->fund_source ?: __('No fund source') }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $application->requirements->count() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $application->status->label() }}</span>
                                    </td>
                                    <td class="max-w-xs px-6 py-4 text-sm text-gray-700">{{ $application->remarks ?: __('No remarks') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('student.scholarship-renewals.show', $application) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No renewal applications yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($applications->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">{{ $applications->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
