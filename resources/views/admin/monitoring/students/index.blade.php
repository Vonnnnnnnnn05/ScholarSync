<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Central Monitoring') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Student Profile Management') }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="mb-5 flex gap-2">
                <x-text-input name="search" class="block w-full max-w-md" :value="$search" placeholder="Search student name or ID" />
                <x-primary-button>{{ __('Search') }}</x-primary-button>
            </form>
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Student') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Course') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Campus') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-6 py-4 text-sm"><div class="font-semibold text-gray-950">{{ $student->fullName() }}</div><div class="text-xs text-gray-500">{{ $student->student_id_number }}</div></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $student->course ?: __('Not set') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $student->campus ?: __('Not set') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ Str::headline($student->status) }}</td>
                                    <td class="px-6 py-4 text-right text-sm"><a href="{{ route('admin.monitoring.students.show', $student) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('Manage') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No students found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($students->hasPages())<div class="border-t border-gray-200 px-6 py-4">{{ $students->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
