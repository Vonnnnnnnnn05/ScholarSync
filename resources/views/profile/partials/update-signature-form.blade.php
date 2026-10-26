<section
    x-data="{ showPad: {{ $user->hasSignature() ? 'false' : 'true' }} }"
    class="space-y-6"
>
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-800 ring-1 ring-emerald-700/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                </span>
                <h3 class="text-lg font-bold text-gray-950">
                    {{ __('Official Digital Signature') }}
                </h3>
            </div>
            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-gray-600">
                {{ __('Digitize your signature using the signature pad. Your digital signature will be securely affixed to approved Certificates of Non-Grantee and official university scholarship documents.') }}
            </p>
        </div>

        @if ($user->hasSignature())
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-700/20">
                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                {{ __('Signature Active & Verified') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                {{ __('Signature Required') }}
            </span>
        @endif
    </header>

    @if (session('status') === 'signature-updated')
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900 shadow-xs">
            ✓ {{ __('Official digital signature has been successfully saved and activated.') }}
        </div>
    @elseif (session('status') === 'signature-deleted')
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-semibold text-gray-700 shadow-xs">
            {{ __('Digital signature has been removed.') }}
        </div>
    @endif

    @if ($user->hasSignature())
        <!-- Current Signature Display Card -->
        <div class="rounded-2xl border border-gray-200 bg-gradient-to-b from-gray-50/50 to-white p-5 shadow-xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Current Signature on File') }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Recorded on :date', ['date' => $user->signature_updated_at?->format('M d, Y h:i A') ?? 'N/A']) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="showPad = !showPad"
                        class="inline-flex min-h-9 items-center justify-center rounded-xl border border-emerald-700/20 bg-white px-3.5 py-1.5 text-xs font-semibold text-emerald-800 shadow-xs transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700"
                    >
                        <span x-text="showPad ? 'Hide Signature Pad' : 'Re-draw / Replace Signature'"></span>
                    </button>

                    <form method="POST" action="{{ route('profile.signature.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your stored digital signature?');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex min-h-9 items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-xs transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        >
                            {{ __('Remove') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Signature Preview Card -->
            <div class="mt-4 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-6 shadow-inner">
                <img
                    src="{{ $user->signatureDataUri() }}"
                    alt="{{ $user->name }} Signature"
                    class="h-24 max-w-sm object-contain drop-shadow-xs"
                >
                <div class="mt-3 w-48 border-t border-gray-400 text-center pt-1">
                    <p class="text-xs font-bold text-gray-900 uppercase tracking-wider">{{ $user->name }}</p>
                    <p class="text-[11px] text-gray-500">{{ __('Scholarship Chairman / OSAS Director') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Signature Pad Form Container -->
    <div
        x-show="showPad"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="space-y-4"
    >
        <form method="POST" action="{{ route('profile.signature.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <x-input-label :value="__('Draw Your Official Signature Below')" class="mb-2 font-bold text-gray-900" />
                <x-signature-pad
                    name="signature_data"
                    :height="210"
                    prompt="Sign naturally using your mouse, stylus pen, or finger touch"
                    :required="true"
                />
                <x-input-error :messages="$errors->get('signature_data')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>
                    {{ __('Save & Digitize Signature') }}
                </x-primary-button>

                @if ($user->hasSignature())
                    <button
                        type="button"
                        @click="showPad = false"
                        class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                    >
                        {{ __('Cancel') }}
                    </button>
                @endif
            </div>
        </form>
    </div>
</section>
