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
                @if (session('status'))
                    <div class="rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                        {{ session('status') }}
                    </div>
                @endif

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase text-emerald-700">{{ __('Current Status') }}</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950">{{ $certificateRequest->status->label() }}</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                {{ __('Verify the uploaded Official Receipt before the request moves forward.') }}
                            </p>
                        </div>
                        <a href="{{ route('admin.official-receipts.download', $certificateRequest) }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                            {{ __('Download OR') }}
                        </a>
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
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Verification Actions') }}</h3>
                    <div class="mt-5 flex flex-col gap-4 sm:flex-row">
                        <form method="POST" action="{{ route('admin.official-receipts.verify', $certificateRequest) }}">
                            @csrf
                            @method('PATCH')
                            <x-primary-button>
                                {{ __('Verify OR') }}
                            </x-primary-button>
                        </form>

                        <form method="POST" action="{{ route('admin.official-receipts.approve', $certificateRequest) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-yellow-500 px-4 py-2 text-sm font-semibold text-emerald-950 shadow-sm transition hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:opacity-50" @disabled($certificateRequest->status !== \App\Enums\CertificateRequestStatus::Verified)>
                                {{ __('Approve & Generate PDF') }}
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.official-receipts.reject', $certificateRequest) }}" class="mt-6">
                        @csrf
                        @method('PATCH')
                        <x-input-label for="remarks" :value="__('Rejection Remarks')" />
                        <textarea id="remarks" name="remarks" rows="4" class="mt-1 block w-full rounded-md border-emerald-900/20 bg-white text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>{{ old('remarks') }}</textarea>
                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                        <div class="mt-4">
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                {{ __('Reject OR') }}
                            </button>
                        </div>
                    </form>
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
