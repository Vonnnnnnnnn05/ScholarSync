<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Official Receipt Review') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Request #:id', ['id' => $certificateRequest->id]) }}
                </h2>
            </div>
            <a href="{{ route('admin.official-receipts.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Back to List') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase text-emerald-700">{{ __('Current Status') }}</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $certificateRequest->status->label() }}</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                {{ __('Review the uploaded Official Receipt and approve with digital signature to generate the certificate.') }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.official-receipts.preview-certificate', $certificateRequest) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md border border-emerald-700/30 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                                <svg class="mr-1.5 h-3.5 w-3.5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                {{ __('Preview Certificate') }}
                            </a>
                            <a href="{{ route('admin.official-receipts.view', $certificateRequest) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md bg-emerald-800 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                                {{ __('View OR') }}
                            </a>
                            <a href="{{ route('admin.official-receipts.download', $certificateRequest) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                {{ __('Download OR') }}
                            </a>
                            @if ($certificateRequest->isCertificateAvailable())
                                <a href="{{ route('admin.certificates.view', $certificateRequest->certificate) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-md bg-emerald-800 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    {{ __('View Certificate') }}
                                </a>
                                <a href="{{ route('admin.certificates.download', $certificateRequest->certificate) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                                    {{ __('Download') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Submitted') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->created_at->format('M d, Y h:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Verified At') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->verified_at?->format('M d, Y h:i A') ?? __('Not verified') }}</dd>
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
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Verification & Approval') }}</h3>

                    @if ($certificateRequest->status !== \App\Enums\CertificateRequestStatus::Approved)
                        <!-- Single Direct Approval & Digital Signature Section -->
                        <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 space-y-4" x-data="{ showNewPad: {{ auth()->user()->hasSignature() ? 'false' : 'true' }} }">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-700 text-white shadow-xs">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">{{ __('Digital Sign-off & Direct Approval') }}</h4>
                                        <p class="text-xs text-gray-600">{{ __('Affix your official digital signature and generate the student certificate.') }}</p>
                                    </div>
                                </div>

                                @if (auth()->user()->hasSignature())
                                    <button
                                        type="button"
                                        @click="showNewPad = !showNewPad"
                                        class="text-xs font-semibold text-emerald-800 hover:text-emerald-950 underline"
                                    >
                                        <span x-text="showNewPad ? 'Use saved signature' : 'Draw new signature for this certificate'"></span>
                                    </button>
                                @endif
                            </div>

                            @if (auth()->user()->hasSignature())
                                <div x-show="!showNewPad" class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-3 shadow-xs">
                                    <div class="h-14 w-36 flex items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50/50 p-1">
                                        <img src="{{ auth()->user()->signatureDataUri() }}" alt="Saved Signature" class="max-h-full max-w-full object-contain">
                                    </div>
                                    <div class="text-xs">
                                        <p class="font-bold text-emerald-900">{{ __('Using Saved Digital Signature') }}</p>
                                        <p class="text-gray-500">{{ auth()->user()->name }} • {{ __('Ready to stamp on generated PDF') }}</p>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.official-receipts.approve', $certificateRequest) }}" class="space-y-4">
                                @csrf
                                @method('PATCH')

                                <div x-show="showNewPad" x-cloak>
                                    <x-signature-pad
                                        name="signature_data"
                                        :height="170"
                                        prompt="Draw your official signature to approve and seal this certificate"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-yellow-500 px-6 py-2.5 text-sm font-bold text-emerald-950 shadow-md transition hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">
                                        {{ __('Approve & Generate PDF') }}
                                    </button>
                                    <a href="{{ route('admin.official-receipts.preview-certificate', $certificateRequest) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-xs transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                                        <svg class="mr-2 h-4 w-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        {{ __('Preview Certificate PDF') }}
                                    </a>
                                </div>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('admin.official-receipts.reject', $certificateRequest) }}" class="mt-6 border-t border-gray-100 pt-6">
                            @csrf
                            @method('PATCH')
                            <x-input-label for="remarks" :value="__('Rejection Remarks (If rejecting)')" />
                            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full rounded-md border-emerald-900/20 bg-white text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" placeholder="{{ __('Provide remarks if Official Receipt is invalid...') }}" required>{{ old('remarks') }}</textarea>
                            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                            <div class="mt-3">
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-md bg-red-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    {{ __('Reject OR') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                            ✓ {{ __('This certificate request has been approved and the numbered PDF is generated.') }}
                        </div>
                    @endif
                </section>

                <!-- Dedicated Certificate Document Preview Panel -->
                <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200" x-data="{ expanded: true }">
                    <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50/70 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-800 text-white shadow-xs">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-gray-900">{{ __('Certificate Document Preview') }}</h3>
                                    @if ($certificateRequest->isCertificateAvailable())
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                            {{ __('Final Generated PDF') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                            {{ __('Live Draft Preview') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ __('Preview the official Certificate of Non-Grantee with student information and your digitized signature.') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('admin.official-receipts.preview-certificate', $certificateRequest) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-gray-50"
                            >
                                <svg class="h-3.5 w-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                                {{ __('Open in New Window') }}
                            </a>
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
                            >
                                <span x-text="expanded ? 'Hide' : 'Show'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="expanded" class="p-4 bg-slate-100/60">
                        <div class="relative overflow-hidden rounded-lg border border-gray-300 bg-white shadow-xs">
                            <iframe
                                src="{{ route('admin.official-receipts.preview-certificate', $certificateRequest) }}"
                                class="w-full bg-white"
                                style="height: 620px; border: none;"
                                title="{{ __('Certificate Document Preview') }}"
                            ></iframe>
                        </div>
                        <p class="mt-2 text-center text-xs text-gray-500">
                            {{ __('Cannot view the PDF preview in your browser?') }}
                            <a href="{{ route('admin.official-receipts.preview-certificate', $certificateRequest) }}" target="_blank" class="font-semibold text-emerald-800 underline hover:text-emerald-950">
                                {{ __('Click here to open it directly') }}
                            </a>
                        </p>
                    </div>
                </section>

            </div>

            <aside class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h3 class="text-base font-semibold text-gray-950">{{ __('Student Details') }}</h3>
                <dl class="mt-5 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->fullName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->user->email }}</dd>
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
                    <div>
                        <dt class="text-xs font-semibold uppercase text-gray-500">{{ __('Contact') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $certificateRequest->student->contact_number }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</x-app-layout>
