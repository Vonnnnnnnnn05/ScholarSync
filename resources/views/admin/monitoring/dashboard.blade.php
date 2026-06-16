<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Administrator') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Central Monitoring Dashboard') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Total Scholars' => $summary['total_scholars'],
                    'Pending Certificate Requests' => $summary['pending_certificate_requests'],
                    'Verified ORs' => $summary['verified_ors'],
                    'Uploaded Masterlists' => $summary['uploaded_masterlists'],
                    'Pending Evaluations' => $summary['pending_evaluations'],
                    'Approved Records' => $summary['approved_records'],
                ] as $label => $value)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-gray-600">{{ __($label) }}</p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-800">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Recent Certificate Requests') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse ($recentCertificateRequests as $request)
                            <div class="px-6 py-4 text-sm">
                                <div class="font-semibold text-gray-950">{{ $request->student->fullName() }}</div>
                                <div class="text-gray-600">{{ $request->status->label() }} · {{ $request->created_at->format('M d, Y') }}</div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">{{ __('No certificate requests yet.') }}</div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Recent Masterlists') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse ($recentMasterlists as $masterlist)
                            <div class="px-6 py-4 text-sm">
                                <div class="font-semibold text-gray-950">{{ $masterlist->file_name }}</div>
                                <div class="text-gray-600">{{ $masterlist->agency->agency_name }} · {{ Str::headline($masterlist->status) }}</div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-sm text-gray-600">{{ __('No masterlists yet.') }}</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
