<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $student->student_id_number }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $student->fullName() }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.students.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Back to Students') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))<div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>@endif
            <div class="grid gap-6 lg:grid-cols-3">
                <form method="POST" action="{{ route('admin.monitoring.students.update', $student) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:col-span-1">
                    @csrf
                    @method('PATCH')
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Profile') }}</h3>
                    <div class="mt-4 space-y-4">
                        @foreach (['student_id_number' => 'Student ID', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'course' => 'Course', 'year_level' => 'Year Level', 'campus' => 'Campus', 'contact_number' => 'Contact Number'] as $field => $label)
                            <div>
                                <x-input-label :for="$field" :value="__($label)" />
                                <x-text-input :id="$field" :name="$field" class="mt-1 block w-full" :value="old($field, $student->{$field})" />
                                <x-input-error :messages="$errors->get($field)" class="mt-2" />
                            </div>
                        @endforeach
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                <option value="active" @selected(old('status', $student->status) === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected(old('status', $student->status) === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <x-primary-button>{{ __('Save Profile') }}</x-primary-button>
                    </div>
                </form>

                <div class="space-y-6 lg:col-span-2">
                    <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4"><h3 class="text-base font-semibold text-gray-950">{{ __('Scholarship History') }}</h3></div>
                        <div class="divide-y divide-gray-200">
                            @forelse ($student->scholarshipApplications as $application)
                                <div class="px-6 py-4 text-sm"><div class="font-semibold text-gray-950">{{ $application->scholarship_program }}</div><div class="text-gray-600">{{ $application->status->label() }} · {{ $application->fund_source ?: __('No fund source') }}</div></div>
                            @empty
                                <div class="px-6 py-8 text-sm text-gray-600">{{ __('No scholarship applications yet.') }}</div>
                            @endforelse
                        </div>
                    </section>
                    <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                        <div class="border-b border-gray-200 px-6 py-4"><h3 class="text-base font-semibold text-gray-950">{{ __('Certificate Requests') }}</h3></div>
                        <div class="divide-y divide-gray-200">
                            @forelse ($student->certificateRequests as $request)
                                <div class="px-6 py-4 text-sm"><div class="font-semibold text-gray-950">{{ Str::limit($request->purpose, 80) }}</div><div class="text-gray-600">{{ $request->status->label() }} · {{ $request->created_at->format('M d, Y') }}</div></div>
                            @empty
                                <div class="px-6 py-8 text-sm text-gray-600">{{ __('No certificate requests yet.') }}</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
