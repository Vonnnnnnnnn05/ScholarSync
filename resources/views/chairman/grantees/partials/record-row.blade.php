@props(['record', 'batch'])

@php
    $missing = is_array($record->missing_fields_flag) ? $record->missing_fields_flag : json_decode($record->missing_fields_flag ?? '[]', true);
    $status = $record->verification_status;
    $enrollStatus = strtoupper((string) ($record->enrollment_status ?? $record->matchedSisStudent?->enrollment_status ?? ''));
    if ($record->matchedSisStudent && $record->matchedSisStudent->cor_printed === false && $enrollStatus === 'ENROLLED') {
        $enrollStatus = 'NO_COR_PRINTED';
    }
@endphp

<tr class="transition hover:bg-gray-50/75 {{ $status === 'AMBER_CONFLICT' ? 'bg-amber-50/30' : ($status === 'RED_UNMATCHED' ? 'bg-rose-50/20' : '') }}">
    <!-- Row Number -->
    <td class="whitespace-nowrap px-4 py-3.5 text-xs font-mono text-gray-500">
        #{{ $record->row_number }}
    </td>

    <!-- Student Name & Category -->
    <td class="px-4 py-3.5">
        <div class="text-sm font-semibold text-gray-950">
            {{ $record->displayName() }}
        </div>
        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-600/20">
                {{ $record->scholarship_program ?? $record->scholarship_name ?? 'TES-UniFAST' }}
            </span>
            @if($record->source_pdf_name)
                <span class="text-[10px] font-mono text-gray-400 truncate max-w-[150px]" title="{{ $record->source_pdf_name }}">
                    {{ $record->source_pdf_name }}
                </span>
            @endif
        </div>
        @if($record->raw_agency_name && $record->raw_agency_name !== $record->displayName())
            <div class="text-[11px] text-gray-400 italic mt-0.5">
                {{ __('Raw: ') }}{{ $record->raw_agency_name }}
            </div>
        @endif
    </td>

    <!-- Student ID -->
    <td class="whitespace-nowrap px-4 py-3.5">
        @if($record->student_id)
            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono font-medium text-gray-800">
                {{ $record->student_id }}
            </span>
        @else
            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                {{ __('Missing ID') }}
            </span>
        @endif
    </td>

    <!-- Campus & Course -->
    <td class="px-4 py-3.5 text-xs text-gray-700">
        @if($record->campus_id || $record->campus)
            <div class="font-medium text-gray-900 flex items-center gap-1.5">
                <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800 border border-blue-200">
                    {{ $record->campus?->name ?? $record->campus }}
                </span>
            </div>
        @else
            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 border border-amber-200 italic">
                {{ __('Unassigned Campus') }}
            </span>
        @endif
        <div class="text-gray-500 mt-0.5">
            {{ $record->program ?? __('No program') }}
        </div>
    </td>

    <!-- Verification Badge -->
    <td class="whitespace-nowrap px-4 py-3.5">
        @if($status === 'GREEN_VERIFIED')
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                {{ __('GREEN: Verified') }}
            </span>
        @elseif($status === 'AMBER_CONFLICT')
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                <svg class="h-3 w-3 text-amber-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                {{ __('AMBER: Action Needed') }}
            </span>
        @elseif($status === 'RED_UNMATCHED')
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">
                <svg class="h-3 w-3 text-rose-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                {{ __('RED: Ineligible') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                {{ __('Pending') }}
            </span>
        @endif
    </td>

    <!-- Enrollment Status -->
    <td class="whitespace-nowrap px-4 py-3.5 text-xs">
        @if(in_array($enrollStatus, ['ENROLLED', 'REGULAR'], true))
            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                {{ __('ENROLLED') }}
            </span>
        @elseif(in_array($enrollStatus, ['NO_COR_PRINTED', 'NO_COR'], true))
            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                {{ __('NO COR PRINTED') }}
            </span>
        @elseif(in_array($enrollStatus, ['UNENROLLED', 'NOT_ENROLLED', 'WITHDRAWN'], true))
            <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                {{ __('UNENROLLED') }}
            </span>
        @else
            <span class="text-gray-400">—</span>
        @endif
    </td>

    <!-- Remarks & Confidence -->
    <td class="px-4 py-3.5 text-xs text-gray-700 max-w-xs">
        <div class="line-clamp-2 {{ $status === 'AMBER_CONFLICT' ? 'text-amber-900 font-medium' : ($status === 'RED_UNMATCHED' ? 'text-rose-800' : 'text-gray-600') }}">
            {{ $record->remarks ?: ($record->conflict_reason ? str_replace('_', ' ', $record->conflict_reason) : '—') }}
        </div>
        @if($record->confidence_score !== null)
            <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                {{ __('Confidence: ') }}{{ number_format($record->confidence_score * 100, 1) }}%
            </div>
        @endif
    </td>

    <!-- Action / Resolve -->
    <td class="whitespace-nowrap px-4 py-3.5 text-right text-xs">
        @if($status === 'AMBER_CONFLICT' && $batch->status !== 'committed')
            <button
                type="button"
                @click="$dispatch('open-resolve-modal', {
                    recordId: {{ $record->id }},
                    rowNumber: {{ $record->row_number }},
                    name: '{{ addslashes($record->displayName()) }}',
                    studentId: '{{ addslashes($record->student_id ?? '') }}',
                    campus: '{{ addslashes($record->campus ?? '') }}',
                    conflictReason: '{{ addslashes($record->conflict_reason ?? '') }}',
                    candidates: @js($record->candidate_matches ?? [])
                })"
                class="inline-flex items-center gap-1 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                {{ __('Review Match') }}
            </button>
        @elseif($status === 'GREEN_VERIFIED' && $record->matchedSisStudent)
            <span class="text-xs text-gray-500" title="{{ $record->matchedSisStudent->student_name }}">
                <span class="font-mono text-emerald-700 font-semibold">{{ $record->matchedSisStudent->student_id_number }}</span>
            </span>
        @else
            <span class="text-xs text-gray-400">—</span>
        @endif
    </td>
</tr>

