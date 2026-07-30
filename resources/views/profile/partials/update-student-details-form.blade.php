<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Student Personal Details') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Update your student profile, campus, course, section, and contact details.') }}
        </p>
    </header>

    <form
        method="post"
        action="{{ route('profile.student-details.update') }}"
        class="mt-6 space-y-6"
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
        @method('patch')

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="student_id_number" :value="__('Student ID Number')" />
                <x-text-input id="student_id_number" name="student_id_number" class="mt-1 block w-full" :value="old('student_id_number', $student?->student_id_number)" required />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('student_id_number')" />
            </div>

            <div>
                <x-input-label for="contact_number" :value="__('Contact Number')" />
                <x-text-input id="contact_number" name="contact_number" class="mt-1 block w-full" :value="old('contact_number', $student?->contact_number)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('contact_number')" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" name="first_name" class="mt-1 block w-full" :value="old('first_name', $student?->first_name)" required autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('first_name')" />
            </div>

            <div>
                <x-input-label for="middle_name" :value="__('Middle Name')" />
                <x-text-input id="middle_name" name="middle_name" class="mt-1 block w-full" :value="old('middle_name', $student?->middle_name)" autocomplete="additional-name" />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('middle_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" name="last_name" class="mt-1 block w-full" :value="old('last_name', $student?->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('last_name')" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
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
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('campus')" />
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
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('course')" />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="year_level" :value="__('Year Level')" />
                <select id="year_level" name="year_level" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                    <option value="">{{ __('Select year') }}</option>
                    @foreach ($academicOptions['dropdowns']['year_levels'] ?? [] as $yearLevel)
                        <option value="{{ $yearLevel }}" @selected(old('year_level', $student?->year_level) === $yearLevel)>{{ $yearLevel }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('year_level')" />
            </div>

            <div>
                <x-input-label for="section" :value="__('Section')" />
                <x-text-input id="section" name="section" class="mt-1 block w-full" :value="old('section', $student?->section)" required />
                <x-input-error class="mt-2" :messages="$errors->getBag('studentDetails')->get('section')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Student Details') }}</x-primary-button>

            @if (session('status') === 'student-details-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

