<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Continuing Scholarship') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Upload Renewal Requirements') }}</h2>
            </div>
            <a href="{{ route('student.scholarship-renewals.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ __('View Applications') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('student.scholarship-renewals.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Scholarship Details') }}</h3>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="scholarship_program" :value="__('Scholarship Program')" />
                            <x-text-input id="scholarship_program" name="scholarship_program" class="mt-1 block w-full" :value="old('scholarship_program')" required />
                            <x-input-error :messages="$errors->get('scholarship_program')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="fund_source" :value="__('Fund Source')" />
                            <x-text-input id="fund_source" name="fund_source" class="mt-1 block w-full" :value="old('fund_source')" />
                            <x-input-error :messages="$errors->get('fund_source')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Required Documents') }}</h3>
                    <div class="mt-5 space-y-4">
                        @foreach ($requiredDocuments as $field => $label)
                            <div>
                                <x-input-label :for="$field" :value="__($label)" />
                                <input id="{{ $field }}" name="{{ $field }}" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block min-h-11 w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700" required>
                                <x-input-error :messages="$errors->get($field)" class="mt-2" />
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm text-gray-600">{{ __('Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 5 MB per file.') }}</p>
                </section>

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Submit Renewal') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
