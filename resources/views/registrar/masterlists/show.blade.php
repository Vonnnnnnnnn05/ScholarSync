<x-app-layout>
<div class="space-y-6 p-6">
    <div><p class="text-sm font-semibold text-purple-700">{{ $batch->campus->name }}</p><h1 class="text-2xl font-bold">Automatic Verification Review</h1><p class="text-sm text-gray-600">{{ Str::headline($batch->status) }}</p></div>
    <x-status-alerts/>
    @if($errors->has('return'))<p class="rounded bg-red-50 p-3 text-red-900">{{ $errors->first('return') }}</p>@endif
    @if($errors->has('reverify'))<p class="rounded bg-red-50 p-3 text-red-900">{{ $errors->first('reverify') }}</p>@endif
    <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-emerald-900">{{ __('Campus enrollment data') }}</p>
                <p class="mt-1 text-sm text-emerald-800">{{ trans_choice(':count enrollment record available|:count enrollment records available', $enrollmentRecordCount, ['count' => number_format($enrollmentRecordCount)]) }}</p>
                @if($reverificationCount > 0)
                    <p class="mt-1 text-xs text-emerald-700">{{ trans_choice(':count unresolved exception will be checked again.|:count unresolved exceptions will be checked again.', $reverificationCount, ['count' => number_format($reverificationCount)]) }}</p>
                @endif
            </div>
            @if(in_array($batch->status, ['awaiting_registrar_review', 'verification_failed'], true) && $reverificationCount > 0)
                @if($enrollmentRecordCount > 0)
                    <form method="POST" action="{{ route('registrar.batches.reverify', $batch) }}">
                        @csrf
                        <x-confirm-submit message="Run automatic verification again for {{ number_format($reverificationCount) }} unresolved exception record(s)? Existing audit snapshots and manual resolutions will be preserved.">
                            {{ __('Run Automatic Verification Again') }}
                        </x-confirm-submit>
                    </form>
                @else
                    <a href="{{ route('registrar.enrolled-students.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        {{ __('Upload Enrollment Records First') }}
                    </a>
                @endif
            @elseif(in_array($batch->status, ['verification_queued', 'verification_processing'], true))
                <span class="inline-flex rounded-md bg-white px-3 py-2 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-700/20">{{ __('Automatic verification is in progress') }}</span>
            @endif
        </div>
    </section>
    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach(['total' => 'Total', 'qualified' => 'Qualified', 'not_qualified' => 'Not Qualified', 'needs_review' => 'Needs Review', 'not_enrolled' => 'Not Enrolled', 'no_cor_printed' => 'No COR'] as $key => $label)
            <div class="rounded-lg bg-white p-4 shadow"><p class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</p><p class="mt-1 text-2xl font-bold">{{ $summary[$key] }}</p></div>
        @endforeach
    </div>
    <nav class="flex flex-wrap gap-2" aria-label="Verification filters">
        @foreach(['' => 'All', 'needs_review' => 'Needs Review', 'not_enrolled' => 'Not Enrolled', 'no_cor_printed' => 'No COR', 'not_qualified' => 'Not Qualified', 'resolved' => 'Resolved'] as $value => $label)
            <a href="{{ route('registrar.batches.show', [$batch, 'filter' => $value]) }}" class="rounded px-3 py-2 text-sm font-semibold {{ $filter === $value ? 'bg-purple-700 text-white' : 'bg-white text-purple-800' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('registrar.batches.show', $batch) }}" data-live-official-record-search data-search-url="{{ route('registrar.batches.official-records', $batch) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <label for="student-search" class="text-sm font-semibold text-gray-900">{{ __('Search official enrollment records') }}</label>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
            <input id="student-search" name="student_search" value="{{ $studentSearch }}" autocomplete="off" class="min-h-11 flex-1 rounded-md border-gray-300" placeholder="{{ __('Student name, ID, or course') }}">
            <button class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">{{ __('Search This Campus') }}</button>
        </div>
        <p data-live-search-status class="mt-2 text-xs text-gray-600" aria-live="polite">@if($studentSearch !== ''){{ trans_choice(':count campus record found|:count campus records found', $officialCandidates->count(), ['count' => $officialCandidates->count()]) }}@else{{ __('Type to search this campus without reloading the page.') }}@endif</p>
    </form>
    @php
        $verificationBadgeClass = static fn (string $status): string => match ($status) {
            'matched', 'enrolled', 'cor_printed', 'qualified', 'resolved' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            'unmatched', 'not_enrolled', 'no_cor_printed', 'not_qualified' => 'bg-red-100 text-red-800 ring-red-600/20',
            'possible_match', 'needs_review', 'ambiguous' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        };
        $verificationBadge = static fn (string $type, string $status): string => sprintf(
            '<span data-verification-badge="%s" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset %s">%s</span>',
            e($type),
            $verificationBadgeClass($status),
            e(Str::headline($status)),
        );
    @endphp
    <div class="space-y-4">
        @forelse($records as $record)
        <section id="record-{{ $record->id }}" class="scroll-mt-6 rounded-lg bg-white p-5 shadow">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-bold">{{ $record->student_name }}</h2><div class="mt-1 flex flex-wrap items-center gap-2"><p class="text-sm text-gray-600">Original ID: {{ $record->student_id_number ?: 'Not supplied' }}</p>{!! $verificationBadge('match', $record->match_status) !!}</div></div>{!! $verificationBadge('qualification', $record->final_qualification_status) !!}</div>
            <div class="mt-3 flex flex-wrap gap-2">
                {!! $verificationBadge('enrollment', $record->final_enrollment_status) !!}
                {!! $verificationBadge('cor', $record->final_cor_status) !!}
                <span class="inline-flex items-center gap-1 text-xs text-gray-500">{{ __('Automatic:') }} {!! $verificationBadge('automatic-qualification', $record->automatic_qualification_status) !!}</span>
                @if($record->resolved_at){!! $verificationBadge('resolution', 'resolved') !!}@endif
            </div>
            <p class="mt-2 text-sm text-gray-600">{{ $record->automatic_result_message ?: 'No automatic result message.' }}</p>
            @if($record->resolved_at)<p class="mt-2 text-sm font-semibold text-emerald-800">Resolved by {{ $record->resolver?->name }} on {{ $record->resolved_at->format('M d, Y H:i') }}</p>@endif
            @if($batch->status === 'awaiting_registrar_review' && ($record->final_qualification_status === 'needs_review' || $record->final_enrollment_status !== 'enrolled' || $record->final_cor_status !== 'cor_printed'))
            @if($record->match_status === 'possible_match' && $record->registrarStudent)
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('Suggested Official Match') }}</p>
                    <div class="mt-3 grid gap-3 text-sm md:grid-cols-2">
                        <div><p class="text-xs font-semibold text-gray-500">{{ __('Original masterlist name') }}</p><p class="font-semibold text-gray-950">{{ $record->student_name }}</p></div>
                        <div><p class="text-xs font-semibold text-gray-500">{{ __('Official enrollment record') }}</p><p class="font-semibold text-gray-950">{{ $record->registrarStudent->student_name }}</p><p class="text-gray-600">{{ $record->registrarStudent->student_id_number }} · {{ $record->registrarStudent->course ?: __('Course not set') }}</p><p class="text-gray-600">{{ Str::headline($record->registrarStudent->enrollment_status) }} · {{ $record->registrarStudent->cor_printed ? __('COR Printed') : __('No COR Printed') }}</p></div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('registrar.batches.records.update', [$batch, $record]) }}">@csrf @method('PATCH')
                        <input type="hidden" name="registrar_student_id" value="{{ $record->registrarStudent->id }}">
                        <input type="hidden" name="final_enrollment_status" value="enrolled">
                        <input type="hidden" name="final_cor_status" value="cor_printed">
                        <input type="hidden" name="final_qualification_status" value="qualified">
                        <input type="hidden" name="reason" value="Confirmed suggested official enrollment record for a minor name inconsistency.">
                        <button name="next" value="1" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">{{ __('Confirm Suggested Match') }}</button>
                    </form>
                    <form method="POST" action="{{ route('registrar.batches.records.update', [$batch, $record]) }}">@csrf @method('PATCH')
                        <input type="hidden" name="registrar_student_id" value="">
                        <input type="hidden" name="final_enrollment_status" value="not_enrolled">
                        <input type="hidden" name="final_cor_status" value="no_cor_printed">
                        <input type="hidden" name="final_qualification_status" value="not_qualified">
                        <input type="hidden" name="reason" value="Registrar confirmed that no matching official campus enrollment record exists.">
                        <button name="next" value="1" onclick="return window.confirm('Confirm that no matching official campus enrollment record exists?')" class="inline-flex min-h-11 items-center justify-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('No Matching Official Record') }}</button>
                    </form>
                    </div>
                </div>
            @endif
            @php($candidateOptions = collect([$record->registrarStudent])->filter()->merge($officialCandidates)->unique('id'))
            <form method="POST" action="{{ route('registrar.batches.records.update', [$batch, $record]) }}" class="mt-4 grid gap-3 md:grid-cols-4" data-resolution-draft="registrar-resolution-{{ auth()->id() }}-{{ $batch->id }}-{{ $record->id }}">@csrf @method('PATCH')
                <label class="md:col-span-4"><span class="text-xs font-semibold uppercase text-gray-500">{{ __('Official enrollment record (optional)') }}</span><select name="registrar_student_id" data-official-record-select class="mt-1 w-full rounded-md"><option value="">{{ __('No official record selected — resolve manually') }}</option>@foreach($candidateOptions as $candidate)<option value="{{ $candidate->id }}" @if($record->registrar_student_id === $candidate->id) data-pinned-official-record @endif @selected($record->registrar_student_id === $candidate->id)>{{ $candidate->student_name }} — {{ $candidate->student_id_number }} — {{ $candidate->course ?: __('No course') }}</option>@endforeach</select></label>
                <select name="final_enrollment_status" class="rounded-md"><option value="enrolled">Enrolled</option><option value="not_enrolled">Not Enrolled</option></select>
                <select name="final_cor_status" class="rounded-md"><option value="cor_printed">COR Printed</option><option value="no_cor_printed">No COR Printed</option></select>
                <select name="final_qualification_status" class="rounded-md"><option value="qualified">Qualified</option><option value="not_qualified">Not Qualified</option></select>
                <input name="reason" class="rounded-md" required placeholder="Resolution reason">
                <p class="hidden text-xs font-semibold text-emerald-700 md:col-span-3" data-draft-status>{{ __('Draft saved in this browser session') }}</p>
                <button name="next" value="1" data-save-resolution class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 md:col-start-4">Save and Review Next</button>
            </form>
            @endif
        </section>
        @empty<p class="rounded-lg bg-white p-5">No records match this filter.</p>@endforelse
    </div>
    {{ $records->links() }}
    @if($batch->status === 'awaiting_registrar_review')<form method="POST" action="{{ route('registrar.batches.return', $batch) }}">@csrf<x-confirm-submit message="Return completed verification results?">Return to Coordinator</x-confirm-submit></form>@endif
</div>
</x-app-layout>
