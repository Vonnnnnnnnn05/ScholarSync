<x-app-layout>
<div class="space-y-6 p-6">
    <div><p class="text-sm font-semibold text-purple-700">{{ $batch->campus->name }}</p><h1 class="text-2xl font-bold">Automatic Verification Review</h1><p class="text-sm text-gray-600">{{ Str::headline($batch->status) }}</p></div>
    <x-status-alerts/>
    @if($errors->has('return'))<p class="rounded bg-red-50 p-3 text-red-900">{{ $errors->first('return') }}</p>@endif
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
    <div class="space-y-4">
        @forelse($records as $record)
        <section class="rounded-lg bg-white p-5 shadow">
            <div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-bold">{{ $record->student_name }}</h2><p class="text-sm text-gray-600">Original ID: {{ $record->student_id_number ?: 'Not supplied' }} · Match: {{ Str::headline($record->match_status) }}</p></div><span class="font-semibold">{{ Str::headline($record->final_qualification_status) }}</span></div>
            <div class="mt-3 grid gap-2 text-sm md:grid-cols-3"><p>Enrollment: <b>{{ Str::headline($record->final_enrollment_status) }}</b></p><p>COR: <b>{{ Str::headline($record->final_cor_status) }}</b></p><p>Automatic qualification: <b>{{ Str::headline($record->automatic_qualification_status) }}</b></p></div>
            <p class="mt-2 text-sm text-gray-600">{{ $record->automatic_result_message ?: 'No automatic result message.' }}</p>
            @if($record->resolved_at)<p class="mt-2 text-sm font-semibold text-emerald-800">Resolved by {{ $record->resolver?->name }} on {{ $record->resolved_at->format('M d, Y H:i') }}</p>@endif
            @if($batch->status === 'awaiting_registrar_review' && ($record->final_qualification_status === 'needs_review' || $record->final_enrollment_status !== 'enrolled' || $record->final_cor_status !== 'cor_printed'))
            <form method="POST" action="{{ route('registrar.batches.records.update', [$batch, $record]) }}" class="mt-4 grid gap-3 md:grid-cols-4" data-resolution-draft="registrar-resolution-{{ auth()->id() }}-{{ $batch->id }}-{{ $record->id }}">@csrf @method('PATCH')
                <select name="final_enrollment_status" class="rounded-md"><option value="enrolled">Enrolled</option><option value="not_enrolled">Not Enrolled</option></select>
                <select name="final_cor_status" class="rounded-md"><option value="cor_printed">COR Printed</option><option value="no_cor_printed">No COR Printed</option></select>
                <select name="final_qualification_status" class="rounded-md"><option value="qualified">Qualified</option><option value="not_qualified">Not Qualified</option></select>
                <input name="reason" class="rounded-md" required placeholder="Resolution reason">
                <p class="hidden text-xs font-semibold text-emerald-700 md:col-span-3" data-draft-status>{{ __('Draft saved in this browser session') }}</p>
                <button class="rounded-md bg-purple-700 px-4 py-2 font-semibold text-white md:col-start-4">Save Resolution</button>
            </form>
            @endif
        </section>
        @empty<p class="rounded-lg bg-white p-5">No records match this filter.</p>@endforelse
    </div>
    {{ $records->links() }}
    @if($batch->status === 'awaiting_registrar_review')<form method="POST" action="{{ route('registrar.batches.return', $batch) }}">@csrf<x-confirm-submit message="Return completed verification results?">Return to Coordinator</x-confirm-submit></form>@endif
</div>
</x-app-layout>
