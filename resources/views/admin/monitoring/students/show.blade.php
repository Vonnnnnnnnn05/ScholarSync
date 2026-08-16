<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-900">{{ $student->fullName() }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.monitoring.students.update', $student) }}" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
            @csrf @method('PATCH')
            <h3 class="font-semibold">{{ __('Profile') }}</h3>
            <div class="mt-4 space-y-4">
                @foreach (['student_id_number' => 'Student ID', 'first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'course' => 'Course', 'year_level' => 'Year Level', 'section' => 'Section', 'campus' => 'Campus', 'contact_number' => 'Contact Number'] as $field => $label)
                    <div><x-input-label :for="$field" :value="__($label)" /><x-text-input :id="$field" :name="$field" class="mt-1 w-full" :value="old($field, $student->{$field})" /></div>
                @endforeach
                <select name="status" class="min-h-11 w-full rounded-md border-gray-300"><option value="active" @selected($student->status === 'active')>Active</option><option value="inactive" @selected($student->status === 'inactive')>Inactive</option></select>
                <x-primary-button>{{ __('Save Profile') }}</x-primary-button>
            </div>
        </form>
        <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 lg:col-span-2">
            <div class="border-b px-6 py-4"><h3 class="font-semibold">{{ __('Certificate Requests') }}</h3></div>
            @forelse ($student->certificateRequests as $request)
                <div class="border-b px-6 py-4 text-sm"><strong>{{ Str::limit($request->purpose, 80) }}</strong><p>{{ $request->status->label() }}</p></div>
            @empty
                <div class="px-6 py-8 text-sm text-gray-600">{{ __('No certificate requests yet.') }}</div>
            @endforelse
        </section>
    </div></div>
</x-app-layout>
