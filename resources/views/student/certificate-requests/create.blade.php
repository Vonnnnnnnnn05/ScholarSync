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
            <form
                method="POST"
                action="{{ route('student.certificate-requests.store') }}"
                enctype="multipart/form-data"
                class="space-y-6"
                x-data="{
                    campuses: @js($academicOptions['campuses'] ?? []),
                    selectedCampus: @js(old('campus', $student?->campus ?? '')),
                    selectedCourse: @js(old('course', $student?->course ?? '')),
                    programsForCampus() {
                        const campus = this.campuses.find((item) => item.name === this.selectedCampus);

                        return campus ? campus.programs : [];
                    },
                }"
            >
                @csrf

                @if(!empty($isGrantee))
                    <div class="rounded-lg border-l-4 border-amber-500 bg-amber-50 p-5 shadow-sm">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 text-amber-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-amber-800">{{ __('Active Scholarship Grantee Notice') }}</h3>
                                <p class="mt-1 text-sm text-amber-700">
                                    {{ __('Our records indicate that you are currently an active scholarship grantee. According to OSAS policy, students currently enjoying a scholarship grant are not eligible to request a Certificate of Non-Grantee.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($errors->has('grantee_restriction'))
                    <div class="rounded-lg border-l-4 border-red-500 bg-red-50 p-4 shadow-sm">
                        <p class="text-sm font-medium text-red-800">{{ $errors->first('grantee_restriction') }}</p>
                    </div>
                @endif

                <fieldset @if(!empty($isGrantee)) disabled class="opacity-75" @endif class="space-y-6">
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
                            <x-input-label for="campus" :value="__('Campus')" />
                            <select
                                id="campus"
                                name="campus"
                                x-model="selectedCampus"
                                @change="selectedCourse = ''"
                                class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                                required
                            >
                                <option value="">{{ __('Select campus') }}</option>
                                @foreach ($academicOptions['campuses'] ?? [] as $campus)
                                    <option value="{{ $campus['name'] }}">{{ $campus['name'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('campus')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="course" :value="__('Course')" />
                            <select
                                id="course"
                                name="course"
                                x-model="selectedCourse"
                                :disabled="! selectedCampus"
                                class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm disabled:bg-gray-100 disabled:text-gray-500 focus:border-emerald-700 focus:ring-emerald-700"
                                required
                            >
                                <option value="" x-text="selectedCampus ? 'Select course' : 'Select campus first'"></option>
                                <template x-for="program in programsForCampus()" :key="program">
                                    <option :value="program" x-text="program"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('course')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="year_level" :value="__('Year Level')" />
                            <select
                                id="year_level"
                                name="year_level"
                                class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                                required
                            >
                                <option value="">{{ __('Select year') }}</option>
                                @foreach ($academicOptions['dropdowns']['year_levels'] ?? [] as $yearLevel)
                                    <option value="{{ $yearLevel }}" @selected(old('year_level', $student?->year_level) === $yearLevel)>
                                        {{ $yearLevel }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
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
                </fieldset>

                <div class="flex justify-end">
                    @if(!empty($isGrantee))
                        <button type="button" disabled class="cursor-not-allowed rounded-md bg-gray-400 px-4 py-2 text-sm font-semibold text-white opacity-60">
                            {{ __('Submission Disabled (Active Grantee)') }}
                        </button>
                    @else
                        <x-primary-button>
                            {{ __('Submit Request') }}
                        </x-primary-button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
