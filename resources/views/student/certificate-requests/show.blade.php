<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Certificate Request') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Request #:id', ['id' => $certificateRequest->id]) }}
                </h2>
            </div>
            <a href="{{ route('student.certificate-requests.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Back to History') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase text-emerald-700">{{ __('Current Status') }}</p>
                        <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $certificateRequest->status->label() }}</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600">
                            {{ __('Track the request from submission to verification and approval. Rejected requests will show remarks from the office once available.') }}
                        </p>
                    </div>

                    @if ($certificateRequest->isCertificateAvailable())
                        <a href="{{ route('student.certificate-requests.certificate.download', $certificateRequest) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                            {{ $certificateRequest->certificate ? __('Download Certificate') : __('Download Sample DOCX') }}
                        </a>
                    @endif
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    @foreach ([\App\Enums\CertificateRequestStatus::Pending, \App\Enums\CertificateRequestStatus::Verified, \App\Enums\CertificateRequestStatus::Approved] as $status)
                        <div class="rounded-lg border p-4 {{ $certificateRequest->status->step() >= $status->step() && $certificateRequest->status !== \App\Enums\CertificateRequestStatus::Rejected ? 'border-emerald-700 bg-emerald-50' : 'border-gray-200 bg-white' }}">
                            <p class="text-sm font-semibold {{ $certificateRequest->status->step() >= $status->step() && $certificateRequest->status !== \App\Enums\CertificateRequestStatus::Rejected ? 'text-emerald-900' : 'text-gray-500' }}">
                                {{ $status->label() }}
                            </p>
                            <p class="mt-2 text-xs leading-5 text-gray-600">
                                @if ($status === \App\Enums\CertificateRequestStatus::Pending)
                                    {{ __('Submitted and waiting for office review.') }}
                                @elseif ($status === \App\Enums\CertificateRequestStatus::Verified)
                                    {{ __('Official receipt and request details reviewed.') }}
                                @else
                                    {{ __('Certificate is ready for release/download.') }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>

                @if ($certificateRequest->status === \App\Enums\CertificateRequestStatus::Rejected)
                    <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        {{ __('This request was rejected. Please review the remarks and submit a new request if needed.') }}
                    </div>
                @endif
            </section>

            <section class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-2">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Request Details') }}</h3>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Submitted') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->created_at->format('M d, Y h:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Certificate Download') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $certificateRequest->isCertificateAvailable() ? __('Available') : __('Not available yet') }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Purpose') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">{{ $certificateRequest->purpose }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Remarks') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">{{ $certificateRequest->remarks ?: __('No remarks yet') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Student Details') }}</h3>
                    <dl class="mt-5 space-y-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->fullName() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Student ID') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->student_id_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Course / Year') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->course }} / {{ $certificateRequest->student->year_level }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Campus') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->campus }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
