<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Student Certificate Request') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Request Certificate of No Scholarship') }}
                </h2>
            </div>
            <a href="{{ route('student.certificate-requests.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                {{ __('View History') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('student.certificate-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Student Details') }}</h3>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="student_id_number" :value="__('Student ID Number')" />
                            <x-text-input id="student_id_number" name="student_id_number" class="mt-1 block w-full" :value="old('student_id_number', $student?->student_id_number)" required />
                            <x-input-error :messages="$errors->get('student_id_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="contact_number" :value="__('Contact Number')" />
                            <x-text-input id="contact_number" name="contact_number" class="mt-1 block w-full" :value="old('contact_number', $student?->contact_number)" required />
                            <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="first_name" :value="__('First Name')" />
                            <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" :value="old('first_name', $student?->first_name)" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="middle_name" :value="__('Middle Name')" />
                            <x-text-input id="middle_name" name="middle_name" class="mt-1 block w-full" :value="old('middle_name', $student?->middle_name)" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Last Name')" />
                            <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" :value="old('last_name', $student?->last_name)" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="course" :value="__('Course')" />
                            <x-text-input id="course" name="course" class="mt-1 block w-full" :value="old('course', $student?->course)" required />
                            <x-input-error :messages="$errors->get('course')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="year_level" :value="__('Year Level')" />
                            <x-text-input id="year_level" name="year_level" class="mt-1 block w-full" :value="old('year_level', $student?->year_level)" required />
                            <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="campus" :value="__('Campus')" />
                            <x-text-input id="campus" name="campus" class="mt-1 block w-full" :value="old('campus', $student?->campus)" required />
                            <x-input-error :messages="$errors->get('campus')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Request Details') }}</h3>

                    <div class="mt-5">
                        <x-input-label for="purpose" :value="__('Purpose')" />
                        <textarea id="purpose" name="purpose" rows="5" class="mt-1 block w-full rounded-md border-emerald-900/20 bg-white text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>{{ old('purpose') }}</textarea>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Briefly state where and why you need the Certificate of No Scholarship.') }}</p>
                        <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                    </div>

                    <div class="mt-5">
                        <x-input-label for="official_receipt" :value="__('Official Receipt')" />
                        <input id="official_receipt" name="official_receipt" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block min-h-11 w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700" required>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Accepted files: PDF, JPG, JPEG, PNG. Maximum size: 5 MB.') }}</p>
                        <x-input-error :messages="$errors->get('official_receipt')" class="mt-2" />
                    </div>
                </section>

                <div class="flex justify-end">
                    <x-primary-button>
                        {{ __('Submit Request') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
