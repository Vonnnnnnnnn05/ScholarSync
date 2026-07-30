<x-guest-layout>
    <form
        method="POST"
        action="{{ route('register') }}"
        x-data="{
            campuses: @js($academicOptions['campuses'] ?? []),
            selectedCampus: @js(old('campus', '')),
            selectedCourse: @js(old('course', '')),
            programsForCampus() {
                const campus = this.campuses.find((item) => item.name === this.selectedCampus);

                return campus ? campus.programs : [];
            },
        }"
    >
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="student_id_number" :value="__('Student ID Number')" />
                <x-text-input id="student_id_number" class="mt-1 block w-full" type="text" name="student_id_number" :value="old('student_id_number')" required autofocus />
                <x-input-error :messages="$errors->get('student_id_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" class="mt-1 block w-full" type="text" name="first_name" :value="old('first_name')" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="middle_name" :value="__('Middle Name')" />
                <x-text-input id="middle_name" class="mt-1 block w-full" type="text" name="middle_name" :value="old('middle_name')" autocomplete="additional-name" />
                <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" class="mt-1 block w-full" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="year_level" :value="__('Year Level')" />
                <select id="year_level" name="year_level" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                    <option value="">{{ __('Select year') }}</option>
                    @foreach ($academicOptions['dropdowns']['year_levels'] ?? [] as $yearLevel)
                        <option value="{{ $yearLevel }}" @selected(old('year_level') === $yearLevel)>{{ $yearLevel }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="section" :value="__('Section')" />
                <x-text-input id="section" class="mt-1 block w-full" type="text" name="section" :value="old('section')" required />
                <x-input-error :messages="$errors->get('section')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="contact_number" :value="__('Contact Number')" />
                <x-text-input id="contact_number" class="mt-1 block w-full" type="text" name="contact_number" :value="old('contact_number')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
            </div>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-emerald-900/70 hover:text-emerald-950 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-700" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
