<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('chairman.grantees.index') }}" class="text-xs font-semibold text-emerald-700 hover:underline">
                        &larr; {{ __('Back to Grantee Lists') }}
                    </a>
                    <span class="text-gray-300">&bull;</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Batch #') }}{{ $batch->id }}</span>
                </div>
                <h2 class="text-xl font-extrabold leading-tight text-gray-900 mt-1 flex items-center gap-2 flex-wrap">
                    <span>{{ $batch->scholarship_name !== 'Auto-Detected' ? $batch->scholarship_name : 'Agency Grantees' }}</span>
                    <span class="text-gray-400 font-normal">&bull;</span>
                    <span class="text-gray-700 font-medium">{{ $batch->agency_name }}</span>
                    @if($batch->campus)
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 border border-blue-200">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $batch->campus->name }}
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-gray-500">
                    {{ $batch->academic_year }} &bull; {{ $batch->semester }} &bull; {{ __('Source: ') }}<span class="font-mono">{{ $batch->original_file_name }}</span>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <!-- Export to Excel Button -->
                <a
                    href="{{ route('chairman.grantees.export', $batch) }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-4 py-2 text-xs font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-50"
                    title="{{ __('Download official grantee roster as an Excel spreadsheet') }}"
                >
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('Export to Excel') }}
                </a>

                @if($batch->status !== 'committed')
                    <!-- Send/Deliver to Campus Coordinator Button -->
                    @if($batch->campus_id)
                        <form method="POST" action="{{ route('chairman.grantees.deliver-to-coordinator', $batch) }}">
                            @csrf
                            <button
                                type="submit"
                                title="{{ __('Deliver batch records to the designated Campus Scholarship Coordinator for validation') }}"
                                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl {{ $batch->delivered_to_coordinator_at ? 'bg-indigo-700 hover:bg-indigo-600' : 'bg-blue-600 hover:bg-blue-500' }} px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span>{{ $batch->delivered_to_coordinator_at ? __('Re-send to Coordinator') : __('Send to Campus Coordinator') }}</span>
                            </button>
                        </form>
                    @endif

                    <!-- Commit / Finalize Batch Button -->
                    <form method="POST" action="{{ route('chairman.grantees.commit', $batch) }}" onsubmit="return confirm('Confirm and finalize this grantee list? All verified students will be flagged as active scholarship grantees in ScholarSync.');">
                        @csrf
                        <button
                            type="submit"
                            @if(!in_array($batch->status, ['endorsed_to_chairman', 'verified'])) disabled @endif
                            title="{{ !in_array($batch->status, ['endorsed_to_chairman', 'verified']) ? 'Awaiting Campus Coordinator & Registrar verification before finalizing' : 'Finalize verified grantees' }}"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 disabled:opacity-70"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Confirm & Finalize Grantees') }}
                        </button>
                    </form>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-100 px-4 py-2 text-xs font-bold text-emerald-900 ring-1 ring-inset ring-emerald-600/20">
                        <svg class="h-4 w-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ __('Finalized by ') }}{{ $batch->committer?->name ?? 'Chairman' }} {{ __('on ') }}{{ $batch->committed_at?->format('M d, Y') }}
                    </span>
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
            @include('chairman.grantees.partials.batch-stats', ['batch' => $batch])

            <!-- Amber Resolution Alert Notice if conflicts exist -->
            @if($batch->amber_count > 0)
                <div class="rounded-2xl border-l-4 border-amber-500 bg-amber-50 p-5 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-amber-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-amber-900">
                                {{ __('Chairman Resolution Required: ') }}{{ $batch->amber_count }} {{ __('Conflicting Record(s)') }}
                            </h3>
                            <p class="mt-1 text-xs text-amber-800">
                                {{ __('The verification engine detected potential duplicate scholarships (Double-Grant) or multiple candidate name matches. Please review each Amber row below and click "Resolve" to select the official student record or mark as unmatched. All conflicts must be resolved before committing this batch.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Bulk Campus Destination & Coordinator Delivery Banner (Sets ALL Records At Once) -->
            @if($batch->status !== 'committed')
                <div class="rounded-2xl border border-blue-200 bg-blue-50/90 p-5 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-700 text-white shadow-xs">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-blue-950">{{ __('Campus Destination & Delivery (Applies to ALL Records At Once)') }}</h4>
                            <p class="text-xs text-blue-800 mt-0.5">
                                @if(($unassignedCampusCount ?? 0) > 0)
                                    <span class="font-bold text-blue-950">{{ $unassignedCampusCount }} {{ __('records') }}</span> {{ __('need campus routing. Choose a campus destination to deliver all at once without 1-by-1 manual assignment:') }}
                                @else
                                    {{ __('All records currently have a campus. You can re-route ALL records in this batch to a specific Campus Coordinator below:') }}
                                @endif
                            </p>
                            @if($batch->delivered_to_coordinator_at)
                                <div class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-emerald-800 flex-wrap">
                                    <span class="flex h-2 w-2 rounded-full bg-emerald-600"></span>
                                    <span>{{ __('Delivered to ') }}<strong>{{ $batch->campus?->name }} Coordinator</strong> {{ __('on ') }}{{ $batch->delivered_to_coordinator_at->format('M d, Y h:i A') }}</span>
                                    @if($batch->sent_to_registrar_at && !$batch->registrar_verified_at)
                                        <span class="ml-1 rounded-md bg-amber-100 px-2 py-0.5 font-bold text-amber-900 border border-amber-300">
                                            👉 {{ __('Forwarded to Campus Registrar (Under Verification)') }}
                                        </span>
                                    @endif
                                    @if($batch->registrar_verified_at)
                                        <span class="ml-1 rounded-md bg-emerald-100 px-2 py-0.5 font-bold text-emerald-900 border border-emerald-300">
                                            ✓ {{ __('Verified by Registrar on ') }}{{ $batch->registrar_verified_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                    @if($batch->coordinator_endorsed_at)
                                        <span class="ml-1 rounded-md bg-emerald-100 px-2 py-0.5 font-bold text-emerald-900 border border-emerald-300">
                                            ✓ {{ __('Endorsed by Coordinator on ') }}{{ $batch->coordinator_endorsed_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('chairman.grantees.assign-fallback-campus', $batch) }}" class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                        @csrf
                        <select name="campus_id" required class="rounded-xl border-blue-300 bg-white py-2 px-3 text-xs text-blue-950 font-medium shadow-xs focus:border-blue-600 focus:ring-blue-600">
                            <option value="">{{ __('Select Campus Destination...') }}</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}" {{ $batch->campus_id === $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="apply_scope" value="all" title="{{ __('Assign every single student in this batch to this campus destination') }}" class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-600">
                            {{ __('Assign to ALL Records (' . $batch->total_rows . ')') }}
                        </button>
                        @if(($unassignedCampusCount ?? 0) > 0)
                            <button type="submit" name="apply_scope" value="unassigned" title="{{ __('Only assign records that are currently unassigned') }}" class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl border border-blue-600 bg-white px-3 py-2 text-xs font-bold text-blue-900 shadow-xs transition hover:bg-blue-50">
                                {{ __('Unassigned Only (' . $unassignedCampusCount . ')') }}
                            </button>
                        @endif
                    </form>
                </div>
            @endif

            <!-- Campus Verification & Coordinator Endorsement Findings -->
            @if($batch->registrar_verified_at || $batch->coordinator_endorsed_at || $batch->sent_to_registrar_at)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 flex-wrap gap-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">✓</span>
                                {{ __('Official Campus Verification & Endorsement Results') }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ __('Official results returned by the :campus Campus Registrar and Scholarship Coordinator.', ['campus' => $batch->campus?->name]) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('chairman.grantees.export', $batch) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-600 bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-700"
                                title="{{ __('Export verified campus roster to Excel (.xlsx)') }}"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>{{ __('Export to Excel') }}</span>
                            </a>
                            @if($batch->status === 'endorsed_to_chairman')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-300">
                                    ✓ {{ __('Verified & Ready to Finalize') }}
                                </span>
                            @elseif($batch->status === 'verified_by_registrar')
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800 border border-blue-300">
                                    ✓ {{ __('Verified by Registrar (With Coordinator)') }}
                                </span>
                            @elseif($batch->status === 'sent_to_registrar')
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 border border-amber-300">
                                    ⏳ {{ __('Under Registrar Verification') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Registrar Findings -->
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-950 flex items-center gap-1.5">
                                    🎓 {{ __('Campus Registrar Official Verification') }}
                                </span>
                                @if($batch->registrar_verified_at)
                                    <span class="text-emerald-700 font-semibold text-[11px]">{{ $batch->registrar_verified_at->format('M d, Y h:i A') }}</span>
                                @else
                                    <span class="text-amber-700 font-semibold text-[11px]">⏳ {{ __('In Progress') }}</span>
                                @endif
                            </div>
                            <div class="text-emerald-900">
                                <div><strong>{{ __('Verified By:') }}</strong> {{ $batch->registrarVerifiedBy?->name ?? 'Campus Registrar' }}</div>
                                <div class="mt-1 flex gap-2 font-semibold">
                                    <span class="text-emerald-700">{{ number_format($batch->green_count) }} {{ __('Enrolled (COR)') }}</span> &bull;
                                    <span class="text-amber-700">{{ number_format($batch->amber_count) }} {{ __('No COR') }}</span> &bull;
                                    <span class="text-rose-700">{{ number_format($batch->red_count) }} {{ __('Unmatched') }}</span>
                                </div>
                            </div>
                            @if($batch->registrar_notes)
                                <div class="mt-2 rounded-lg bg-white p-2.5 border border-emerald-200 text-gray-800 italic">
                                    <strong>{{ __('Registrar Notes:') }}</strong> "{{ $batch->registrar_notes }}"
                                </div>
                            @endif
                        </div>

                        <!-- Coordinator Endorsement -->
                        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-950 flex items-center gap-1.5">
                                    🏢 {{ __('Campus Coordinator Endorsement') }}
                                </span>
                                @if($batch->coordinator_endorsed_at)
                                    <span class="text-blue-700 font-semibold text-[11px]">{{ $batch->coordinator_endorsed_at->format('M d, Y h:i A') }}</span>
                                @else
                                    <span class="text-gray-500 font-semibold text-[11px]">Pending Review</span>
                                @endif
                            </div>
                            <div class="text-blue-900">
                                <div><strong>{{ __('Endorsed By:') }}</strong> {{ $batch->coordinatorEndorsedBy?->name ?? ($batch->deliveredToCoordinatorBy ? 'Awaiting Coordinator' : 'Not Delivered') }}</div>
                                <div><strong>{{ __('Campus:') }}</strong> {{ $batch->campus?->name ?? 'Unassigned' }}</div>
                            </div>
                            @if($batch->coordinator_notes)
                                <div class="mt-2 rounded-lg bg-white p-2.5 border border-blue-200 text-gray-800 italic">
                                    <strong>{{ __('Coordinator Notes:') }}</strong> "{{ $batch->coordinator_notes }}"
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filter Tabs & Staged Records Table -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Category Program Tabs -->
                <div class="border-b border-gray-200 bg-emerald-950/5 px-6 py-2.5 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
                        <span class="text-gray-400 uppercase tracking-wider text-[11px] mr-1">{{ __('Program Category:') }}</span>
                        <a
                            href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'program' => 'all'])) }}"
                            class="rounded-md px-3 py-1 transition {{ ($currentProgram ?? 'all') === 'all' ? 'bg-emerald-900 text-white shadow-xs' : 'text-gray-700 hover:bg-gray-200/70' }}"
                        >
                            {{ __('All Programs') }} ({{ $batch->total_rows }})
                        </a>
                        @php
                            $knownPrograms = ['TES-UniFAST', 'TDP', 'CMSP'];
                            $availablePrograms = array_unique(array_merge($knownPrograms, array_keys($programStats ?? [])));
                        @endphp
                        @foreach($availablePrograms as $pName)
                            @php $pCount = ($programStats[$pName] ?? 0); @endphp
                            @if($pCount > 0 || in_array($pName, $knownPrograms, true))
                                <a
                                    href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'program' => $pName])) }}"
                                    class="rounded-md px-3 py-1 transition {{ ($currentProgram ?? 'all') === $pName ? 'bg-emerald-800 text-white shadow-xs' : 'text-gray-700 hover:bg-gray-200/70' }}"
                                >
                                    {{ $pName }} ({{ $pCount }})
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Status Filter Pills -->
                <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-3 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2 overflow-x-auto">
                        <span class="text-gray-400 uppercase tracking-wider text-[11px] mr-1">{{ __('Filter Students:') }}</span>
                        <a
                            href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'all'])) }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'all' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
                        >
                            {{ __('All Students (') }}{{ $batch->total_rows }}{{ (')') }}
                        </a>
                        <a
                            href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'GREEN_VERIFIED'])) }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'GREEN_VERIFIED' ? 'bg-emerald-700 text-white shadow-sm' : 'text-emerald-800 hover:bg-emerald-50' }}"
                        >
                            {{ __('✓ Verified Enrolled (') }}{{ $batch->green_count }}{{ (')') }}
                        </a>
                        <a
                            href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'AMBER_CONFLICT'])) }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'AMBER_CONFLICT' ? 'bg-amber-600 text-white shadow-sm' : 'text-amber-800 hover:bg-amber-50' }}"
                        >
                            {{ __('⚠ Needs Review (') }}{{ $batch->amber_count }}{{ (')') }}
                        </a>
                        <a
                            href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'RED_UNMATCHED'])) }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'RED_UNMATCHED' ? 'bg-rose-600 text-white shadow-sm' : 'text-rose-800 hover:bg-rose-50' }}"
                        >
                            {{ __('✕ Not Enrolled / Unmatched (') }}{{ $batch->red_count }}{{ (')') }}
                        </a>
                        @if($batch->pending_count > 0)
                            <a
                                href="{{ route('chairman.grantees.show', array_merge(request()->query(), ['batch' => $batch, 'status' => 'PENDING'])) }}"
                                class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'PENDING' ? 'bg-gray-700 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
                            >
                                {{ __('Pending (') }}{{ $batch->pending_count }}{{ (')') }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-600">
                            <tr>
                                <th class="px-4 py-3">{{ __('Row') }}</th>
                                <th class="px-4 py-3">{{ __('Grantee & Category') }}</th>
                                <th class="px-4 py-3">{{ __('Student ID') }}</th>
                                <th class="px-4 py-3">{{ __('Campus & Course') }}</th>
                                <th class="px-4 py-3">{{ __('Verification Badge') }}</th>
                                <th class="px-4 py-3">{{ __('Enrollment Status') }}</th>
                                <th class="px-4 py-3">{{ __('Remarks & Match') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($records as $rec)
                                @include('chairman.grantees.partials.record-row', ['record' => $rec, 'batch' => $batch])
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-xs text-gray-500">
                                        {{ __('No staged records match this filter.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                @if($records->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>

            <!-- Include Interactive Amber Resolution Modal -->
            @include('chairman.grantees.partials.resolve-modal', ['batch' => $batch])
        </div>
    </div>
</x-app-layout>
