<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Student Certificate Request') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Certificate Request History') }}
                </h2>
            </div>
            <a href="{{ route('student.certificate-requests.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                {{ __('New Request') }}
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

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Purpose') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Remarks') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Certificate') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($certificateRequests as $certificateRequest)
                                @php
                                    $statusClass = match ($certificateRequest->status) {
                                        \App\Enums\CertificateRequestStatus::Pending => 'bg-yellow-100 text-yellow-900',
                                        \App\Enums\CertificateRequestStatus::Verified => 'bg-blue-100 text-blue-900',
                                        \App\Enums\CertificateRequestStatus::Rejected => 'bg-red-100 text-red-900',
                                        \App\Enums\CertificateRequestStatus::Approved => 'bg-emerald-100 text-emerald-900',
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $certificateRequest->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="max-w-xs px-6 py-4 text-sm text-gray-900">
                                        {{ Str::limit($certificateRequest->purpose, 70) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $certificateRequest->status->label() }}
                                        </span>
                                    </td>
                                    <td class="max-w-xs px-6 py-4 text-sm text-gray-700">
                                        {{ $certificateRequest->remarks ?: __('No remarks yet') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        @if ($certificateRequest->isCertificateAvailable())
                                            <a href="{{ route('student.certificate-requests.certificate.download', $certificateRequest) }}" class="font-semibold text-emerald-800 underline hover:text-emerald-950">
                                                {{ $certificateRequest->certificate ? __('Download DOCX') : __('Download Sample DOCX') }}
                                            </a>
                                        @else
                                            {{ __('Not available') }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('student.certificate-requests.show', $certificateRequest) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No certificate requests yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($certificateRequests->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $certificateRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
