<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Scholarship Evaluation') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Renewal Applications') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="mb-5 flex flex-wrap gap-2">
                <a href="{{ route('evaluator.scholarship-renewals.index') }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === '' ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">{{ __('All') }}</a>
                @foreach ($statuses as $status)
                    <a href="{{ route('evaluator.scholarship-renewals.index', ['status' => $status->value]) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ $activeStatus === $status->value ? 'bg-emerald-800 text-white' : 'bg-white text-emerald-800 ring-1 ring-emerald-700/20' }}">{{ $status->label() }}</a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Documents') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($applications as $application)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-semibold">{{ $application->student->fullName() }}</div>
                                        <div class="text-xs text-gray-500">{{ $application->student->student_id_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        {{ $application->scholarship_program }}
                                        <div class="text-xs text-gray-500">{{ $application->fund_source ?: __('No fund source') }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $application->requirements->count() }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-900">{{ $application->status->label() }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('evaluator.scholarship-renewals.show', $application) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No renewal applications found.') }}</td>
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
