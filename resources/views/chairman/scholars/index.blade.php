<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Verified Scholarship Grantees Directory') }}
                </h2>
            </div>
            <a href="{{ route('chairman.grantees.upload') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Upload New Masterlist') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Summary KPI Metric Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Scholars -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Total Verified Scholars') }}</span>
                        <span class="rounded-full bg-emerald-50 p-2 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-gray-950">{{ number_format($totalCount) }}</span>
                        <span class="text-xs font-semibold text-emerald-700">{{ __('Awardees') }}</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">{{ __('Officially flagged & locked in Module A') }}</p>
                </div>

                <!-- TES-UniFAST -->
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('TES-UniFAST') }}</span>
                        <span class="rounded-full bg-emerald-100 p-2 text-emerald-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-emerald-950">{{ number_format($tesCount) }}</span>
                        <span class="text-xs font-bold text-emerald-700">{{ $totalCount > 0 ? round(($tesCount / $totalCount) * 100) . '%' : '0%' }}</span>
                    </div>
                    <p class="mt-2 text-xs text-emerald-800/80">{{ __('Tertiary Education Subsidy') }}</p>
                </div>

                <!-- TDP -->
                <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-800">{{ __('TDP Grantees') }}</span>
                        <span class="rounded-full bg-blue-100 p-2 text-blue-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-blue-950">{{ number_format($tdpCount) }}</span>
                        <span class="text-xs font-bold text-blue-700">{{ $totalCount > 0 ? round(($tdpCount / $totalCount) * 100) . '%' : '0%' }}</span>
                    </div>
                    <p class="mt-2 text-xs text-blue-800/80">{{ __('Tulong-Dunong Program') }}</p>
                </div>

                <!-- CMSP -->
                <div class="rounded-2xl border border-purple-200 bg-purple-50/40 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-800">{{ __('CMSP Scholars') }}</span>
                        <span class="rounded-full bg-purple-100 p-2 text-purple-800">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold text-purple-950">{{ number_format($cmspCount) }}</span>
                        <span class="text-xs font-bold text-purple-700">{{ $totalCount > 0 ? round(($cmspCount / $totalCount) * 100) . '%' : '0%' }}</span>
                    </div>
                    <p class="mt-2 text-xs text-purple-800/80">{{ __('CHED Merit Scholarship Program') }}</p>
                </div>
            </div>

            <!-- Filter Toolbar Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('chairman.scholars.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <!-- Search Input -->
                    <div>
                        <x-input-label for="search" :value="__('Search Student Name / ID')" />
                        <div class="relative mt-1">
                            <input
                                type="text"
                                id="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="e.g. Dela Cruz or 2024-..."
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700 pl-9"
                            >
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Target Campus Filter -->
                    <div>
                        <x-input-label for="campus" :value="__('Filter by Campus')" />
                        <select id="campus" name="campus" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="all">{{ __('All Campuses') }}</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}" {{ (string)$selectedCampus === (string)$campus->id ? 'selected' : '' }}>
                                    {{ $campus->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Scholarship Program Filter -->
                    <div>
                        <x-input-label for="program" :value="__('Filter by Scholarship Category')" />
                        <select id="program" name="program" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <option value="all">{{ __('All Scholarship Programs') }}</option>
                            @foreach($programs as $p)
                                <option value="{{ $p }}" {{ $selectedProgram === $p ? 'selected' : '' }}>
                                    {{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex flex-1 min-h-10 items-center justify-center gap-2 rounded-lg bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            {{ __('Filter Directory') }}
                        </button>

                        @if($search !== '' || $selectedCampus !== 'all' || $selectedProgram !== 'all')
                            <a
                                href="{{ route('chairman.scholars.index') }}"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                title="{{ __('Reset Filters') }}"
                            >
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Scholar Directory Table -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 font-bold uppercase tracking-wider text-gray-600">
                            <tr>
                                <th class="px-6 py-3.5 text-left">{{ __('Scholar') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Student ID') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Scholarship Program') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Campus') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Course / Academic Program') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Verification Status') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Source Batch / Document') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($scholars as $scholar)
                                @php
                                    $studentName = $isLegacy ? $scholar->student_name : ($scholar->student_name ?: $scholar->displayName());
                                    $studentId = $isLegacy ? $scholar->student_id_number : $scholar->student_id;
                                    $prog = $isLegacy ? ($scholar->scholarship_program ?: $scholar->masterlist?->program_name) : ($scholar->scholarship_program ?: $scholar->scholarship_name);
                                    $campusName = $isLegacy ? ($scholar->campus?->name ?? '—') : ($scholar->campus ?? $scholar->campusRef?->name ?? '—');
                                    $courseName = $isLegacy ? '—' : ($scholar->program ?? '—');
                                    $sourceDoc = $isLegacy ? ($scholar->masterlist?->file_name ?? 'Legacy Upload') : ($scholar->source_pdf_name ?? $scholar->batch?->original_file_name ?? 'Batch #' . $scholar->batch_id);
                                @endphp
                                <tr class="hover:bg-gray-50/75 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-800 uppercase text-xs">
                                                {{ substr($studentName, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-950">{{ $studentName }}</div>
                                                <div class="text-[11px] text-gray-400">Awardee</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-semibold text-gray-800">
                                        {{ $studentId ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(str_contains(strtoupper((string)$prog), 'TES'))
                                            <span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-900 border border-emerald-300">
                                                {{ $prog }}
                                            </span>
                                        @elseif(str_contains(strtoupper((string)$prog), 'TDP') || str_contains(strtoupper((string)$prog), 'TULONG'))
                                            <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-bold text-blue-900 border border-blue-300">
                                                {{ $prog }}
                                            </span>
                                        @elseif(str_contains(strtoupper((string)$prog), 'CMSP') || str_contains(strtoupper((string)$prog), 'MERIT'))
                                            <span class="inline-flex items-center rounded-md bg-purple-100 px-2 py-1 text-xs font-bold text-purple-900 border border-purple-300">
                                                {{ $prog }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-bold text-gray-800 border border-gray-300">
                                                {{ $prog ?: 'Scholar' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800 border border-blue-200">
                                            {{ $campusName }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">
                                        {{ $courseName }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            {{ __('Active Grantee') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 font-mono text-[11px] truncate max-w-xs" title="{{ $sourceDoc }}">
                                        {{ $sourceDoc }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        <p class="mt-3 text-sm font-semibold text-gray-900">{{ __('No verified scholars found.') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            @if($search !== '' || $selectedCampus !== 'all' || $selectedProgram !== 'all')
                                                {{ __('Try adjusting your search criteria or resetting filters.') }}
                                            @else
                                                {{ __('Upload external agency masterlists to automatically parse, verify, and populate this directory.') }}
                                            @endif
                                        </p>
                                        @if($search !== '' || $selectedCampus !== 'all' || $selectedProgram !== 'all')
                                            <a href="{{ route('chairman.scholars.index') }}" class="mt-4 inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                                {{ __('Reset Filters') }}
                                            </a>
                                        @else
                                            <a href="{{ route('chairman.grantees.upload') }}" class="mt-4 inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                                {{ __('Upload Masterlist Now') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($scholars->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $scholars->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
