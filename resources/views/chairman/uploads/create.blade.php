<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Upload Scholar Masterlist') }}
                </h2>
            </div>
            <a href="{{ route('chairman.uploads.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('Manage Masterlists') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('chairman.uploads.preview') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Agency Details') }}</h3>
                    <div class="mt-5">
                        <x-input-label for="agency_id" :value="__('Agency')" />
                        <select id="agency_id" name="agency_id" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                            <option value="">{{ __('Select an agency') }}</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}" @selected((string) old('agency_id') === (string) $agency->id)>{{ $agency->agency_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('agency_id')" class="mt-2" />
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('CSV Masterlist') }}</h3>

                    <div class="mt-5">
                        <x-input-label for="masterlist" :value="__('CSV File')" />
                        <input id="masterlist" name="masterlist" type="file" accept=".csv,text/csv" class="mt-1 block min-h-11 w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700" required>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Accepted file: CSV. Maximum size: 5 MB.') }}</p>
                        <x-input-error :messages="$errors->get('masterlist')" class="mt-2" />
                    </div>

                    <div class="mt-5 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-950">
                        <span class="font-semibold">{{ __('Required columns:') }}</span>
                        {{ implode(', ', $requiredColumns) }}
                    </div>
                </section>

                <div class="flex justify-end">
                    <x-primary-button>
                        {{ __('Preview Masterlist') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
