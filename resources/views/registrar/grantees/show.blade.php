<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('registrar.batches.index') }}" class="text-xs font-bold text-emerald-800 hover:underline">
                        &larr; {{ __('Back to Verification Queue') }}
                    </a>
                    <span class="text-gray-300">&bull;</span>
                    <span class="text-xs font-mono font-semibold text-gray-500">BATCH #{{ $batch->id }}</span>
                </div>
                <h2 class="text-xl font-bold leading-tight text-gray-950 mt-1 flex items-center gap-2">
                    <span>{{ $batch->agency_name ?? 'Agency' }} {{ __('Beneficiary Verification') }}</span>
                    <span class="text-gray-400 font-normal">&bull;</span>
                    <span class="text-emerald-800 font-extrabold">{{ $batch->campus?->name }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ __('Official Registrar Enrollment Verification Module') }} &bull;
                    {{ __('Source: ') }}<span class="font-mono text-gray-700">{{ $batch->original_file_name }}</span>
                    @if($batch->sent_to_registrar_at)
                        &bull; {{ __('Forwarded by Coordinator: ') }}<span class="font-semibold text-gray-700">{{ $batch->sent_to_registrar_at->format('M d, Y h:i A') }}</span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('registrar.grantees.export', $batch) }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-50"
                    title="{{ __('Export this verified campus roster to Excel (.xlsx)') }}"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('Export to Excel (.xlsx)') }}
                </a>

                @if($batch->status === 'verified_by_registrar' || $batch->status === 'endorsed_to_chairman')
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-3.5 py-2 text-xs font-bold text-emerald-900 border border-emerald-300 shadow-xs">
                        <svg class="h-4 w-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ __('Verified & Returned on ') }}{{ $batch->registrar_verified_at?->format('M d, Y') ?? 'Done' }}
                    </span>
                @else
                    <a href="#return-form" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Sign Off & Return to Coordinator') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-status-alerts/>

            <!-- Coordinator Review Banner -->
            @if($batch->coordinator_to_registrar_notes)
                <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-900 shadow-sm flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <span class="font-bold uppercase tracking-wider text-amber-800">{{ __('Campus Coordinator Review Instructions:') }}</span>
                        <p class="mt-0.5 text-amber-900 font-medium">{{ $batch->coordinator_to_registrar_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Grantees to Verify') }}</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ number_format($batch->total_rows) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format($campusStudentCount) }} {{ __('official campus SIS records') }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Verified (Enrolled + COR)') }}</p>
                    <p class="mt-2 text-2xl font-black text-emerald-900">{{ number_format($batch->green_count) }}</p>
                    <p class="text-xs text-emerald-700 mt-1">{{ $batch->total_rows > 0 ? round(($batch->green_count / $batch->total_rows) * 100) : 0 }}% {{ __('confirmed valid') }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">{{ __('Needs Review (No COR)') }}</p>
                    <p class="mt-2 text-2xl font-black text-amber-900">{{ number_format($batch->amber_count) }}</p>
                    <p class="text-xs text-amber-700 mt-1">{{ __('Enrolled but COR not printed') }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-700">{{ __('Unmatched / Inactive') }}</p>
                    <p class="mt-2 text-2xl font-black text-rose-900">{{ number_format($batch->red_count) }}</p>
                    <p class="text-xs text-rose-700 mt-1">{{ __('Not enrolled in campus SIS') }}</p>
                </div>
            </div>

            <!-- Verification Action Box -->
            <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">⚖️</span>
                            {{ __('Campus Registrar Verification Controls') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('Execute official verification against your campus enrollment repository (:campus) to validate enrolled status and printed Certificate of Registration (COR).', ['campus' => $batch->campus?->name]) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <form method="POST" action="{{ route('registrar.grantees.verify-batch', $batch) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>{{ $batch->verified_at ? __('Re-Verify Batch (Re-check Exceptions)') : __('Run Official Enrollment Verification') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Program Category Breakdown Tabs -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 mr-2">{{ __('Program:') }}</span>
                <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => 'all', 'status' => $currentStatus, 'search' => $search]) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition {{ $currentProgram === 'all' ? 'bg-emerald-800 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <span>{{ __('All Programs') }}</span>
                    <span class="rounded-full bg-white/20 px-1.5 py-0.2 text-[10px]">{{ $batch->total_rows }}</span>
                </a>
                @foreach($categoryCounts as $progName => $count)
                    <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $progName, 'status' => $currentStatus, 'search' => $search]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold transition {{ $currentProgram === $progName ? 'bg-emerald-800 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                        <span>{{ $progName }}</span>
                        <span class="rounded-full bg-emerald-200/50 px-1.5 py-0.2 text-[10px] font-mono">{{ $count }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Table Card -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Search & Status Filter Toolbar -->
                <div class="border-b border-gray-100 p-4 space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 mr-2">{{ __('Status:') }}</span>
                        <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $currentProgram, 'status' => 'all', 'search' => $search]) }}"
                           class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $currentStatus === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ __('All Statuses') }}
                        </a>
                        <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $currentProgram, 'status' => 'GREEN_VERIFIED', 'search' => $search]) }}"
                           class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $currentStatus === 'GREEN_VERIFIED' ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                            ✓ {{ __('Verified (Enrolled + COR)') }} ({{ $batch->green_count }})
                        </a>
                        <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $currentProgram, 'status' => 'AMBER_CONFLICT', 'search' => $search]) }}"
                           class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $currentStatus === 'AMBER_CONFLICT' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                            ⏳ {{ __('Needs Review (No COR)') }} ({{ $batch->amber_count }})
                        </a>
                        <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $currentProgram, 'status' => 'RED_UNMATCHED', 'search' => $search]) }}"
                           class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $currentStatus === 'RED_UNMATCHED' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                            ✕ {{ __('Unmatched / Inactive') }} ({{ $batch->red_count }})
                        </a>
                    </div>

                    <form method="GET" action="{{ route('registrar.grantees.show', $batch) }}" class="flex flex-col sm:flex-row gap-2">
                        <input type="hidden" name="program" value="{{ $currentProgram }}">
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                        <x-text-input name="search" :value="$search" placeholder="Search student name or ID..." class="w-full text-xs" />
                        <x-secondary-button type="submit" class="min-h-10 text-xs justify-center">{{ __('Search') }}</x-secondary-button>
                        @if($search)
                            <a href="{{ route('registrar.grantees.show', ['batch' => $batch, 'program' => $currentProgram, 'status' => $currentStatus]) }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-gray-600 uppercase font-semibold tracking-wider text-[11px]">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">{{ __('Grantee Details') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Scholarship Category') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Campus SIS Match') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Registrar Verification') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Notes / Exceptions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($records as $rec)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-4 py-3 font-mono text-gray-400">{{ $rec->row_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-950">{{ $rec->student_name }}</div>
                                        @if($rec->student_id)
                                            <span class="font-mono text-gray-500 text-[11px]">ID: {{ $rec->student_id }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-200">
                                            {{ $rec->scholarship_program ?? 'General Grantee' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($rec->matchedSisStudent)
                                            <div class="font-semibold text-gray-900">{{ $rec->matchedSisStudent->student_name }}</div>
                                            <div class="text-[11px] text-gray-500 font-mono">{{ $rec->matchedSisStudent->student_id_number }} &bull; {{ $rec->matchedSisStudent->course }}</div>
                                        @else
                                            <span class="text-rose-500 font-semibold italic text-[11px]">{{ __('No match found in campus records') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($rec->verification_status === 'GREEN_VERIFIED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                                ✓ {{ __('Enrolled (COR Printed)') }}
                                            </span>
                                        @elseif($rec->verification_status === 'AMBER_CONFLICT')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-amber-600/20">
                                                ⏳ {{ __('Enrolled (No COR)') }}
                                            </span>
                                        @elseif($rec->verification_status === 'RED_UNMATCHED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-rose-600/20">
                                                ✕ {{ __('Not Enrolled / Unmatched') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-semibold text-gray-600">
                                                {{ __('Pending Verification') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-[11px]">
                                        @if($rec->conflict_reason)
                                            <span class="text-amber-800 font-medium">{{ $rec->conflict_reason }}</span>
                                        @elseif($rec->resolution_notes)
                                            <span class="text-gray-700 italic">{{ $rec->resolution_notes }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                        {{ __('No grantee records found matching your filter.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $records->links() }}
                </div>
            </div>

            <!-- Sign Off & Return to Coordinator Card -->
            <div id="return-form" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-start justify-between flex-wrap gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">✓</span>
                            {{ __('Campus Registrar Sign-Off & Return to Coordinator') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ __('Once verification is finished, sign off the batch with any registrar findings. The batch will be returned to the Campus Scholarship Coordinator to endorse to the Scholarship Chairman.') }}
                        </p>
                    </div>

                    @if($batch->status === 'verified_by_registrar' || $batch->status === 'endorsed_to_chairman')
                        <div class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs text-emerald-900 border border-emerald-200 font-semibold">
                            {{ __('Verified on ') }}{{ $batch->registrar_verified_at?->format('M d, Y h:i A') }}
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('registrar.grantees.return-to-coordinator', $batch) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="notes" :value="__('Registrar Verification Notes & Remarks (Optional)')" class="text-xs font-bold text-gray-700" />
                        <textarea id="notes" name="notes" rows="3"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-emerald-500 focus:ring-emerald-500 text-xs"
                            placeholder="e.g. Official campus enrollment and COR validation verified. All valid students confirmed.">{{ old('notes', $batch->registrar_notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="submit"
                            onclick="return confirm('Confirm and return this officially verified roster to the Campus Scholarship Coordinator?');"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Sign Off & Return Verified List to Coordinator') }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
