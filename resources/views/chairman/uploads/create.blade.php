<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Upload Scholar Masterlist') }}
                </h2>
            </div>
            <a href="{{ route('chairman.uploads.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                {{ __('Manage Masterlists') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('chairman.uploads.preview') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('CSV Masterlist') }}</h3>

                    <div class="mt-5">
                        <x-input-label for="masterlist" :value="__('CSV File')" />
                        <input id="masterlist" name="masterlist" type="file" accept=".csv,text/csv" class="mt-1 block min-h-11 w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700" required>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Accepted file: CSV. Maximum size: 5 MB.') }}</p>
                        <x-input-error :messages="$errors->get('masterlist')" class="mt-2" />
                    </div>

                    <div class="mt-5 space-y-2 text-sm">
                        <div class="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-950">
                            <span class="font-semibold">{{ __('Required columns:') }}</span>
                            {{ implode(', ', $requiredColumns) }}
                        </div>
                        <div class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-950">
                            <span class="font-semibold">{{ __('Auto-detected columns:') }}</span>
                            {{ __('scholarship_program (or program, scholarship_name), student_id_number, fund_source') }}.
                            <span class="block mt-1 text-xs text-emerald-800">{{ __('If the scholarship program column is omitted, the system will automatically derive the program name from the filename.') }}</span>
                        </div>
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
