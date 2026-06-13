<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Central Monitoring') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Transaction Monitoring') }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @foreach ([
                'Certificate Requests and OR Verification' => $certificateRequests,
                'Masterlist Uploads' => $masterlists,
                'Renewal Evaluations' => $evaluations,
            ] as $title => $items)
                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4"><h3 class="text-base font-semibold text-gray-950">{{ __($title) }}</h3></div>
                    <div class="divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <div class="px-6 py-4 text-sm">
                                @if ($title === 'Certificate Requests and OR Verification')
                                    <div class="font-semibold text-gray-950">{{ $item->student->fullName() }}</div>
                                    <div class="text-gray-600">{{ $item->status->label() }} · OR {{ $item->verified_at ? __('verified') : __('not verified') }}</div>
                                @elseif ($title === 'Masterlist Uploads')
                                    <div class="font-semibold text-gray-950">{{ $item->file_name }}</div>
                                    <div class="text-gray-600">{{ $item->agency->agency_name }} · {{ Str::headline($item->status) }}</div>
                                @else
                                    <div class="font-semibold text-gray-950">{{ $item->student->fullName() }}</div>
                                    <div class="text-gray-600">{{ $item->scholarship_program }} · {{ $item->status->label() }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">{{ __('No records found.') }}</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
