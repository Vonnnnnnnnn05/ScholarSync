<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('coordinator.masterlists.index') }}" class="text-xs font-bold text-emerald-800 hover:underline">
                        &larr; {{ __('Back to Campus Batches') }}
                    </a>
                    <span class="text-gray-300">&bull;</span>
                    <span class="text-xs font-mono font-semibold text-gray-500">BATCH #{{ $batch->id }}</span>
                </div>
                <h2 class="text-xl font-bold leading-tight text-gray-950 mt-1 flex items-center gap-2">
                    <span>{{ $batch->agency_name ?? 'Agency' }} {{ __('Grantees') }}</span>
                    <span class="text-gray-400 font-normal">&bull;</span>
                    <span class="text-emerald-800 font-extrabold">{{ $batch->campus?->name }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $batch->academic_year ?? 'Current AY' }} &bull; {{ $batch->semester ?? 'Current Sem' }} &bull;
                    {{ __('Source: ') }}<span class="font-mono text-gray-700">{{ $batch->original_file_name }}</span>
                    @if($batch->delivered_to_coordinator_at)
                        &bull; {{ __('Delivered by Chairman on: ') }}<span class="font-semibold text-gray-700">{{ $batch->delivered_to_coordinator_at->format('M d, Y h:i A') }}</span>
                    @endif
                </p>
            </div>

            <!-- Endorsement Status & Action -->
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('coordinator.grantees.export', $batch) }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-50"
                    title="{{ __('Export this campus roster to Excel (.xlsx)') }}"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('Export to Excel (.xlsx)') }}
                </a>

                @if($batch->coordinator_endorsed_at)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-3.5 py-2 text-xs font-bold text-emerald-900 border border-emerald-300 shadow-xs">
                        <svg class="h-4 w-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ __('Endorsed to Chairman on ') }}{{ $batch->coordinator_endorsed_at->format('M d, Y') }}
                    </span>
                @else
                    <a href="#endorse-form" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('Endorse Roster to Chairman') }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900 shadow-sm flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Batch Summary Statistics -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Campus Roster') }}</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ $batch->total_rows }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('Records in this campus batch') }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Verified (Green)') }}</p>
                    <p class="mt-2 text-2xl font-black text-emerald-900">{{ $batch->green_count }}</p>
                    <p class="text-xs text-emerald-700 mt-1">{{ $batch->total_rows > 0 ? round(($batch->green_count / $batch->total_rows) * 100) : 0 }}% {{ __('confirmed in SIS') }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-700">{{ __('Conflicts (Amber)') }}</p>
                    <p class="mt-2 text-2xl font-black text-amber-900">{{ $batch->amber_count }}</p>
                    <p class="text-xs text-amber-700 mt-1">{{ __('Requires Chairman resolution') }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-700">{{ __('Unmatched (Red)') }}</p>
                    <p class="mt-2 text-2xl font-black text-rose-900">{{ $batch->red_count }}</p>
                    <p class="text-xs text-rose-700 mt-1">{{ __('Not found in campus roster') }}</p>
                </div>
            </div>

            <!-- Workflow Progress Tracker -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">{{ __('Verification Workflow Pipeline') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                    <div class="rounded-xl border p-3 {{ $batch->delivered_to_coordinator_at ? 'border-emerald-300 bg-emerald-50/60 text-emerald-900' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                        <div class="font-bold">1. Chairman Delivery</div>
                        <div class="mt-1 text-[11px]">{{ $batch->delivered_to_coordinator_at ? '✓ Received ' . $batch->delivered_to_coordinator_at->format('M d, Y') : 'Pending' }}</div>
                    </div>
                    <div class="rounded-xl border p-3 {{ $batch->sent_to_registrar_at ? 'border-emerald-300 bg-emerald-50/60 text-emerald-900' : ($batch->status === 'delivered_to_coordinator' ? 'border-amber-400 bg-amber-50 text-amber-900 font-bold ring-2 ring-amber-400' : 'border-gray-200 bg-gray-50 text-gray-500') }}">
                        <div class="font-bold">2. Forward to Registrar</div>
                        <div class="mt-1 text-[11px]">{{ $batch->sent_to_registrar_at ? '✓ Forwarded ' . $batch->sent_to_registrar_at->format('M d, Y') : ($batch->status === 'delivered_to_coordinator' ? '👉 Action Needed' : 'Pending') }}</div>
                    </div>
                    <div class="rounded-xl border p-3 {{ $batch->registrar_verified_at ? 'border-emerald-300 bg-emerald-50/60 text-emerald-900' : ($batch->status === 'sent_to_registrar' ? 'border-amber-400 bg-amber-50 text-amber-900 font-bold ring-2 ring-amber-400' : 'border-gray-200 bg-gray-50 text-gray-500') }}">
                        <div class="font-bold">3. Registrar Verification</div>
                        <div class="mt-1 text-[11px]">{{ $batch->registrar_verified_at ? '✓ Verified ' . $batch->registrar_verified_at->format('M d, Y') : ($batch->status === 'sent_to_registrar' ? '⏳ Under Registrar Review' : 'Pending') }}</div>
                    </div>
                    <div class="rounded-xl border p-3 {{ $batch->coordinator_endorsed_at ? 'border-emerald-300 bg-emerald-50/60 text-emerald-900' : ($batch->status === 'verified_by_registrar' ? 'border-emerald-500 bg-emerald-50 text-emerald-900 font-bold ring-2 ring-emerald-500' : 'border-gray-200 bg-gray-50 text-gray-500') }}">
                        <div class="font-bold">4. Pass to Chairman</div>
                        <div class="mt-1 text-[11px]">{{ $batch->coordinator_endorsed_at ? '✓ Passed ' . $batch->coordinator_endorsed_at->format('M d, Y') : ($batch->status === 'verified_by_registrar' ? '👉 Ready to Endorse' : 'Pending') }}</div>
                    </div>
                </div>
            </div>

            <!-- Coordinator Action Section -->
            <div id="endorse-form" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                @if(!$batch->sent_to_registrar_at || $batch->status === 'delivered_to_coordinator')
                    <!-- Step 2: Forward to Registrar -->
                    <div class="space-y-4">
                        <div class="flex items-start justify-between flex-wrap gap-3 border-b border-gray-100 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">2</span>
                                    {{ __('Review Roster & Forward to Campus Registrar') }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ __('Review the beneficiary records below. Next, forward this batch to the Campus Registrar who holds the official authority to verify student enrollment and printed COR status.') }}
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('coordinator.grantees.forward-to-registrar', $batch) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="forward_notes" class="block text-xs font-bold text-gray-700">{{ __('Instructions or Notes for Campus Registrar (Optional)') }}</label>
                                <textarea id="forward_notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 text-xs text-gray-800 focus:border-emerald-700 focus:ring-emerald-700 shadow-xs" placeholder="{{ __('e.g., Please verify enrollment status and COR printing for these external scholarship beneficiaries.') }}">{{ old('notes', $batch->coordinator_to_registrar_notes) }}</textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    {{ __('Forward to Campus Registrar for Verification') }}
                                </button>
                            </div>
                        </form>
                    </div>

                @elseif($batch->status === 'sent_to_registrar')
                    <!-- Step 3: Awaiting Registrar -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 rounded-xl bg-amber-50 p-4 border border-amber-200 text-xs text-amber-900">
                            <svg class="h-6 w-6 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <h4 class="font-bold text-amber-950">{{ __('Currently with Campus Registrar for Official Enrollment Verification') }}</h4>
                                <p class="mt-0.5 text-amber-800">
                                    {{ __('Forwarded on ') }} {{ $batch->sent_to_registrar_at->format('M d, Y h:i A') }}.
                                    {{ __('The Registrar is verifying these beneficiaries against official campus enrollment records. Once complete, they will return the roster here for your final sign-off.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                @elseif($batch->status === 'verified_by_registrar' || $batch->status === 'endorsed_to_chairman')
                    <!-- Step 4: Registrar Verification Complete, Pass to Chairman -->
                    <div class="space-y-4">
                        <div class="flex items-start justify-between flex-wrap gap-3 border-b border-gray-100 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">✓</span>
                                    {{ __('Registrar Verification Complete — Pass to Scholarship Chairman') }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ __('The Campus Registrar has completed enrollment verification. Review their findings below and pass the verified batch to the Scholarship Chairman.') }}
                                </p>
                            </div>
                            @if($batch->coordinator_endorsed_at)
                                <div class="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs text-emerald-900 border border-emerald-200 font-semibold">
                                    {{ __('Passed to Chairman on ') }}{{ $batch->coordinator_endorsed_at->format('M d, Y h:i A') }}
                                </div>
                            @endif
                        </div>

                        @if($batch->registrar_notes)
                            <div class="rounded-xl bg-emerald-50/70 p-3.5 border border-emerald-200 text-xs text-emerald-900">
                                <strong class="font-bold">{{ __('Campus Registrar Verification Remarks: ') }}</strong>
                                {{ $batch->registrar_notes }}
                            </div>
                        @endif

                        @if(!$batch->coordinator_endorsed_at)
                            <form method="POST" action="{{ route('coordinator.grantees.endorse', $batch) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="endorse_notes" class="block text-xs font-bold text-gray-700">{{ __('Coordinator Final Endorsement Remarks (Optional)') }}</label>
                                    <textarea id="endorse_notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-gray-300 text-xs text-gray-800 focus:border-emerald-700 focus:ring-emerald-700 shadow-xs" placeholder="{{ __('e.g., Verified by Campus Registrar and endorsed for scholarship release.') }}">{{ old('notes', $batch->coordinator_notes) }}</textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('Pass Verified Roster to Scholarship Chairman') }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Filter Tabs & Records Table -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Category Program Tabs -->
                <div class="border-b border-gray-200 bg-emerald-950/5 px-6 py-2.5 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
                        <span class="text-gray-400 uppercase tracking-wider text-[11px] mr-1">{{ __('Program Category:') }}</span>
                        <a
                            href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'program' => 'all'])) }}"
                            class="rounded-md px-3 py-1 transition {{ ($currentProgram ?? 'all') === 'all' ? 'bg-emerald-900 text-white shadow-xs' : 'text-gray-700 hover:bg-gray-200/70' }}"
                        >
                            {{ __('All Programs') }} ({{ $batch->total_rows }})
                        </a>
                        @foreach($categoryCounts as $category => $catCount)
                            <a
                                href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'program' => $category])) }}"
                                class="rounded-md px-3 py-1 transition {{ ($currentProgram ?? '') === $category ? 'bg-emerald-900 text-white shadow-xs' : 'text-gray-700 hover:bg-gray-200/70' }}"
                            >
                                {{ $category }} ({{ $catCount }})
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Status Filter Pills -->
                <div class="border-b border-gray-100 bg-white px-6 py-2.5 flex items-center gap-2 overflow-x-auto text-xs font-medium">
                    <span class="text-gray-400 uppercase tracking-wider text-[11px] mr-1 font-bold">{{ __('Status:') }}</span>
                    <a
                        href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'all'])) }}"
                        class="rounded-full px-3 py-0.5 transition {{ ($currentStatus ?? 'all') === 'all' ? 'bg-gray-900 text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }}"
                    >
                        {{ __('All Records') }} ({{ $batch->total_rows }})
                    </a>
                    <a
                        href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'GREEN_VERIFIED'])) }}"
                        class="rounded-full px-3 py-0.5 transition {{ ($currentStatus ?? '') === 'GREEN_VERIFIED' ? 'bg-emerald-700 text-white font-bold' : 'text-emerald-700 hover:bg-emerald-50' }}"
                    >
                        {{ __('Green Verified') }} ({{ $batch->green_count }})
                    </a>
                    <a
                        href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'AMBER_CONFLICT'])) }}"
                        class="rounded-full px-3 py-0.5 transition {{ ($currentStatus ?? '') === 'AMBER_CONFLICT' ? 'bg-amber-600 text-white font-bold' : 'text-amber-700 hover:bg-amber-50' }}"
                    >
                        {{ __('Amber Conflicts') }} ({{ $batch->amber_count }})
                    </a>
                    <a
                        href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'RED_UNMATCHED'])) }}"
                        class="rounded-full px-3 py-0.5 transition {{ ($currentStatus ?? '') === 'RED_UNMATCHED' ? 'bg-rose-700 text-white font-bold' : 'text-rose-700 hover:bg-rose-50' }}"
                    >
                        {{ __('Red Ineligible') }} ({{ $batch->red_count }})
                    </a>
                    <a
                        href="{{ route('coordinator.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'PENDING'])) }}"
                        class="rounded-full px-3 py-0.5 transition {{ ($currentStatus ?? '') === 'PENDING' ? 'bg-gray-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }}"
                    >
                        {{ __('Pending') }} ({{ $batch->pending_count }})
                    </a>
                </div>

                <!-- Records Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-gray-50/75 text-[11px] font-bold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">{{ __('Grantee & Category') }}</th>
                                <th class="px-4 py-3">{{ __('Student ID') }}</th>
                                <th class="px-4 py-3">{{ __('Campus & Course') }}</th>
                                <th class="px-4 py-3">{{ __('Verification Badge') }}</th>
                                <th class="px-4 py-3">{{ __('Enrollment Status') }}</th>
                                <th class="px-4 py-3">{{ __('Remarks & Match') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($records as $record)
                                <tr class="hover:bg-gray-50/75">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-400">#{{ $record->row_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $record->displayName() }}</div>
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-600/20">
                                                {{ $record->scholarship_program ?? 'TES-UniFAST' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono font-medium text-gray-800">
                                        {{ $record->student_id ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $record->campus?->name ?? $record->campus ?? '—' }}</div>
                                        <div class="text-gray-500 text-[11px]">{{ $record->program ?? '—' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if($record->verification_status === 'GREEN_VERIFIED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                                                <svg class="h-2.5 w-2.5 text-emerald-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                {{ __('Verified') }}
                                            </span>
                                        @elseif($record->verification_status === 'AMBER_CONFLICT')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                                <svg class="h-2.5 w-2.5 text-amber-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                {{ __('Conflict') }}
                                            </span>
                                        @elseif($record->verification_status === 'RED_UNMATCHED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">
                                                <svg class="h-2.5 w-2.5 text-rose-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                {{ __('Ineligible') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold">
                                        @if($record->enrollment_status === 'ENROLLED')
                                            <span class="text-emerald-700">ENROLLED</span>
                                        @elseif($record->enrollment_status === 'NO_COR_PRINTED')
                                            <span class="text-amber-700">NO COR PRINTED</span>
                                        @elseif($record->enrollment_status === 'UNENROLLED')
                                            <span class="text-rose-700">UNENROLLED</span>
                                        @else
                                            <span class="text-gray-400 italic">{{ __('Pending') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 text-[11px]">
                                        {{ $record->remarks ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                        {{ __('No grantee records found for this filter.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($records->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
