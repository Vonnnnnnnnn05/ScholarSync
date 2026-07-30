<x-app-layout>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Registrar</p>
            <h1 class="text-2xl font-bold text-gray-950">Enrolled Student Records</h1>
            <p class="mt-1 text-sm text-gray-600">Maintain the official enrollment source used by the verification microservice.</p>
        </div>

        <x-status-alerts />

        <section class="rounded-md border border-emerald-900/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950">Import Enrollment CSV</h2>
                    <p class="mt-1 text-sm text-gray-600">Upload official registrar enrollment data. Required columns: student_id_number, student_name.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800">Registrar only</span>
            </div>

            <form method="POST" action="{{ route('registrar.enrolled-students.import') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                @csrf
                <div>
                    <x-input-label for="enrollment_csv" :value="__('CSV File')" />
                    <input id="enrollment_csv" name="enrollment_csv" type="file" accept=".csv,text/csv" class="mt-1 block w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100" required />
                    <x-input-error :messages="$errors->get('enrollment_csv')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500">Optional columns: course, year_level, campus, enrollment_status, academic_year, semester.</p>
                </div>
                <x-primary-button class="min-h-11 justify-center">{{ __('Import CSV') }}</x-primary-button>
            </form>
        </section>

        <section class="rounded-md border border-emerald-900/10 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-950">Add Enrolled Student</h2>
            <form method="POST" action="{{ route('registrar.enrolled-students.store') }}" class="mt-5 grid gap-4 lg:grid-cols-3">
                @csrf
                <div>
                    <x-input-label for="student_id_number" :value="__('Student ID Number')" />
                    <x-text-input id="student_id_number" name="student_id_number" class="mt-1 block w-full" :value="old('student_id_number')" required />
                    <x-input-error :messages="$errors->get('student_id_number')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="student_name" :value="__('Student Name')" />
                    <x-text-input id="student_name" name="student_name" class="mt-1 block w-full" :value="old('student_name')" required />
                    <x-input-error :messages="$errors->get('student_name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="course" :value="__('Course')" />
                    <x-text-input id="course" name="course" class="mt-1 block w-full" :value="old('course')" />
                </div>
                <div>
                    <x-input-label for="year_level" :value="__('Year Level')" />
                    <x-text-input id="year_level" name="year_level" class="mt-1 block w-full" :value="old('year_level')" />
                </div>
                <div>
                    <x-input-label for="campus" :value="__('Campus')" />
                    <x-text-input id="campus" name="campus" class="mt-1 block w-full" :value="old('campus')" />
                </div>
                <div>
                    <x-input-label for="enrollment_status" :value="__('Enrollment Status')" />
                    <select id="enrollment_status" name="enrollment_status" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="enrolled">Enrolled</option>
                        <option value="not_enrolled">Not Enrolled</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="academic_year" :value="__('Academic Year')" />
                    <x-text-input id="academic_year" name="academic_year" class="mt-1 block w-full" :value="old('academic_year')" placeholder="2026-2027" />
                </div>
                <div>
                    <x-input-label for="semester" :value="__('Semester')" />
                    <x-text-input id="semester" name="semester" class="mt-1 block w-full" :value="old('semester')" placeholder="1st Semester" />
                </div>
                <div class="flex items-end">
                    <x-primary-button class="min-h-11 w-full justify-center">{{ __('Save Record') }}</x-primary-button>
                </div>
            </form>
        </section>

        <section class="rounded-md border border-emerald-900/10 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                    <x-text-input name="search" class="w-full" :value="$search" placeholder="Search ID, name, course, or campus" />
                    <x-secondary-button class="min-h-11 justify-center">{{ __('Search') }}</x-secondary-button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-5 py-3">Student ID</th>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Course</th>
                            <th class="px-5 py-3">Campus</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Term</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-emerald-900">{{ $student->student_id_number }}</td>
                                <td class="px-5 py-4">{{ $student->student_name }}</td>
                                <td class="px-5 py-4">{{ $student->course ?: 'Not set' }}</td>
                                <td class="px-5 py-4">{{ $student->campus ?: 'Not set' }}</td>
                                <td class="px-5 py-4">{{ str($student->enrollment_status)->headline() }}</td>
                                <td class="px-5 py-4">{{ collect([$student->academic_year, $student->semester])->filter()->implode(' / ') ?: 'Not set' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">No registrar records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-5">{{ $students->links() }}</div>
        </section>
    </div>
</x-app-layout>
