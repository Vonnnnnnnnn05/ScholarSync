<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $campusName }} &bull; {{ __('Campus Registrar') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Enrolled Student Records') }}
                </h2>
                <p class="text-xs text-gray-500">{{ __('Maintain the official enrollment source used by the verification microservice.') }}</p>
            </div>
            @if(($totalCount ?? 0) > 0)
                <form method="POST" action="{{ route('registrar.enrolled-students.clear') }}" onsubmit="return confirm('⚠️ DELETE CONFIRMATION:\n\nAre you sure you want to permanently delete all {{ number_format($totalCount) }} records for {{ $campusName }} ONLY?\n\n• Only {{ $campusName }} data will be removed.\n• Other campuses (such as ACCESS Campus) are completely safe and will NOT be touched.\n• You can upload a new clean list immediately after.\n\nProceed with deleting {{ $campusName }} records?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition">
                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('Delete :campus Records (:count)', ['campus' => $campusName, 'count' => number_format($totalCount)]) }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-status-alerts />

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <a href="{{ route('registrar.enrolled-students.index') }}" class="group rounded-lg border border-emerald-900/10 bg-white p-5 shadow-sm transition hover:border-emerald-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Total Records') }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 group-hover:text-emerald-700">{{ number_format($totalCount ?? 0) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __(':campus repository', ['campus' => $campusName]) }}</p>
                </a>
                <a href="{{ route('registrar.enrolled-students.index', ['status' => 'enrolled']) }}" class="group rounded-lg border border-emerald-900/10 bg-white p-5 shadow-sm transition hover:border-emerald-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">{{ __('Enrolled') }}</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($enrolledCount ?? 0) }}</p>
                    <p class="mt-1 text-xs text-emerald-600">{{ __('Enrolled with COR printed') }}</p>
                </a>
                <a href="{{ route('registrar.enrolled-students.index', ['status' => 'no_cor_printed']) }}" class="group rounded-lg border border-emerald-900/10 bg-white p-5 shadow-sm transition hover:border-amber-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">{{ __('No COR Printed') }}</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($noCorCount ?? 0) }}</p>
                    <p class="mt-1 text-xs text-amber-600">{{ __('Enrolled without printed COR') }}</p>
                </a>
                <a href="{{ route('registrar.enrolled-students.index', ['status' => 'unenrolled']) }}" class="group rounded-lg border border-emerald-900/10 bg-white p-5 shadow-sm transition hover:border-rose-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">{{ __('Unenrolled') }}</p>
                    <p class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($unenrolledCount ?? 0) }}</p>
                    <p class="mt-1 text-xs text-rose-600">{{ __('Not enrolled or inactive') }}</p>
                </a>
            </div>

            <!-- Upload Section -->
            <section class="rounded-lg border border-emerald-900/10 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-gray-950">{{ __('Upload Enrollment Records') }}</h2>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">{{ $campusName }} {{ __('Registrar Only') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Upload official :campus enrollment lists (Excel .xlsx or CSV). Supports the official SKSU SIS export format.', ['campus' => $campusName]) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('registrar.enrolled-students.template') }}" class="inline-flex items-center gap-2 rounded-md border border-emerald-300 bg-white px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('Download Sample Excel Template') }}
                        </a>

                        @if(($totalCount ?? 0) > 0)
                            <form method="POST" action="{{ route('registrar.enrolled-students.clear') }}" onsubmit="return confirm('⚠️ DELETE CONFIRMATION:\n\nAre you sure you want to permanently delete all {{ number_format($totalCount) }} records for {{ $campusName }} ONLY?\n\n• Only {{ $campusName }} data will be removed.\n• Other campuses (such as ACCESS Campus) are completely safe and will NOT be touched.\n• You can upload a new clean list immediately after.\n\nProceed with deleting {{ $campusName }} records?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-rose-300 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100 hover:text-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                    <svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    {{ __('Delete :campus Records', ['campus' => $campusName]) }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-3 rounded-md bg-emerald-50/70 p-3 text-xs text-emerald-900">
                    <p class="font-semibold">{{ __('Supported SKSU Enrollment Columns:') }}</p>
                    <p class="mt-0.5 text-emerald-800 font-mono text-[11px]">
                        Campus | Student ID | LRN | Last Name | First Name | Middle Initial | Course | Year Level | Sex | Email | Contact Number | Enrollment Status
                    </p>
                </div>

                <form method="POST" action="{{ route('registrar.enrolled-students.import') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    @csrf
                    <div>
                        <x-input-label for="enrollment_file" :value="__('Excel or CSV File (.xlsx, .csv)')" />
                        <input id="enrollment_file" name="enrollment_file" type="file" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="mt-1 block w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100" required />
                        <x-input-error :messages="$errors->get('enrollment_file')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Accepted Enrollment Status values: Enrolled, No COR Printed, or Unenrolled.') }}
                        </p>
                    </div>
                    <x-primary-button class="min-h-11 justify-center">{{ __('Upload and Validate') }}</x-primary-button>
                </form>
            </section>

            <!-- Table Section -->
            <section class="rounded-lg border border-emerald-900/10 bg-white shadow-sm">
                <!-- Search & Filters -->
                <div class="border-b border-gray-100 p-5 space-y-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide mr-1">{{ __('Filter:') }}</span>
                        <a href="{{ route('registrar.enrolled-students.index', array_filter(['search' => $search])) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ empty($status) ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('All') }} ({{ number_format($totalCount ?? 0) }})
                        </a>
                        <a href="{{ route('registrar.enrolled-students.index', array_filter(['status' => 'enrolled', 'search' => $search])) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $status === 'enrolled' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                            {{ __('Enrolled') }} ({{ number_format($enrolledCount ?? 0) }})
                        </a>
                        <a href="{{ route('registrar.enrolled-students.index', array_filter(['status' => 'no_cor_printed', 'search' => $search])) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $status === 'no_cor_printed' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                            {{ __('No COR Printed') }} ({{ number_format($noCorCount ?? 0) }})
                        </a>
                        <a href="{{ route('registrar.enrolled-students.index', array_filter(['status' => 'unenrolled', 'search' => $search])) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $status === 'unenrolled' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                            {{ __('Unenrolled') }} ({{ number_format($unenrolledCount ?? 0) }})
                        </a>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                        @if ($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <x-text-input name="search" class="w-full" :value="$search" placeholder="Search ID, name, course, campus, or status (e.g. Enrolled, No COR, Unenrolled)" />
                        <x-secondary-button class="min-h-11 justify-center">{{ __('Search') }}</x-secondary-button>
                        @if ($search || $status)
                            <a href="{{ route('registrar.enrolled-students.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-5 py-3">{{ __('Student ID') }}</th>
                                <th class="px-5 py-3">{{ __('Name') }}</th>
                                <th class="px-5 py-3">{{ __('Course & Year') }}</th>
                                <th class="px-5 py-3">{{ __('Campus') }}</th>
                                <th class="px-5 py-3">{{ __('Enrollment Status') }}</th>
                                <th class="px-5 py-3">{{ __('Term') }}</th>
                                <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse ($students as $student)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-5 py-4 font-mono font-semibold text-emerald-900">{{ $student->student_id_number }}</td>
                                    <td class="px-5 py-4 font-medium text-gray-900">{{ $student->student_name }}</td>
                                    <td class="px-5 py-4">
                                        <div>{{ $student->course ?: 'Not set' }}</div>
                                        @if ($student->year_level)
                                             <span class="text-xs text-gray-500">Year {{ $student->year_level }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">{{ $student->campus ?: 'Not set' }}</td>
                                    <td class="px-5 py-4">
                                        @if ($student->enrollment_status === 'enrolled' && $student->cor_printed)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                {{ __('Enrolled') }}
                                            </span>
                                        @elseif ($student->enrollment_status === 'enrolled' && !$student->cor_printed)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                {{ __('No COR Printed') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                                {{ __('Unenrolled') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs text-gray-500">
                                        {{ collect([$student->academic_year, $student->semester])->filter()->implode(' / ') ?: 'Not set' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <form method="POST" action="{{ route('registrar.enrolled-students.destroy', $student) }}" onsubmit="return confirm('Remove student {{ $student->student_name }} ({{ $student->student_id_number }}) from enrollment records?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center text-xs font-semibold text-rose-600 hover:text-rose-900 transition">
                                                <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                {{ __('Remove') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                        {{ __('No registrar records found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-5">{{ $students->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
