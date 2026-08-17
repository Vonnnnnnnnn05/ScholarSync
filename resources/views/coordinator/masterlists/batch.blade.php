<x-app-layout>
    <div class="space-y-6 p-6">
        <div><p class="text-sm font-semibold text-emerald-700">{{ $batch->campus->name }}</p><h1 class="text-2xl font-bold">Beneficiary Campus Batch</h1><p class="text-sm text-gray-600">{{ $batch->masterlist->file_name }} · {{ Str::headline($batch->status) }}</p></div>
        <x-status-alerts />
        <div class="overflow-x-auto rounded-lg bg-white shadow"><table class="min-w-full"><thead><tr class="border-b"><th class="p-4 text-left">Beneficiary</th><th class="p-4 text-left">Registrar Result</th><th class="p-4 text-left">Remarks</th></tr></thead><tbody>@foreach($records as $record)<tr class="border-b"><td class="p-4">{{ $record->student_name }}</td><td class="p-4">{{ Str::headline($record->verification_status) }}</td><td class="p-4">{{ $record->remarks ?: '—' }}</td></tr>@endforeach</tbody></table></div>
        {{ $records->links() }}
        @if($batch->status === 'with_coordinator')<form method="POST" action="{{ route('coordinator.batches.submit-to-registrar', $batch) }}">@csrf<x-confirm-submit message="Submit this campus batch to the Registrar?">Submit to Registrar</x-confirm-submit></form>@endif
        @if($batch->status === 'returned_to_coordinator')<form method="POST" action="{{ route('coordinator.batches.submit-to-chairman', $batch) }}">@csrf<x-confirm-submit message="Send the Registrar results to the Chairman?">Submit to Chairman</x-confirm-submit></form>@endif
    </div>
</x-app-layout>
