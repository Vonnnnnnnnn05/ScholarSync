<x-app-layout>
    <div class="space-y-6 p-6">
        <div><p class="text-sm font-semibold text-emerald-700">{{ $batch->campus->name }}</p><h1 class="text-2xl font-bold">Beneficiary Campus Batch</h1><p class="text-sm text-gray-600">{{ $batch->masterlist->file_name }} · {{ Str::headline($batch->status) }}</p></div>
        <x-status-alerts />
        <div class="overflow-x-auto rounded-lg bg-white shadow"><table class="min-w-full"><thead><tr class="border-b"><th class="p-4 text-left">Beneficiary</th><th class="p-4 text-left">Enrollment</th><th class="p-4 text-left">COR</th><th class="p-4 text-left">Qualification</th><th class="p-4 text-left">Result message</th></tr></thead><tbody>@foreach($records as $record)<tr class="border-b"><td class="p-4">{{ $record->student_name }}</td><td class="p-4">{{ Str::headline($record->final_enrollment_status) }}</td><td class="p-4">{{ Str::headline($record->final_cor_status) }}</td><td class="p-4">{{ Str::headline($record->final_qualification_status) }}</td><td class="p-4">{{ $record->remarks ?: $record->automatic_result_message ?: '—' }}</td></tr>@endforeach</tbody></table></div>
        @if($batch->status === 'verification_failed')<form method="POST" action="{{ route('coordinator.batches.retry-verification', $batch) }}">@csrf<x-confirm-submit message="Retry failed automatic verification records?">Retry Verification</x-confirm-submit></form>@endif
        {{ $records->links() }}
        @if($batch->status === 'with_coordinator')<form method="POST" action="{{ route('coordinator.batches.submit-to-registrar', $batch) }}">@csrf<x-confirm-submit message="Submit this campus batch to the Registrar?">Submit to Registrar</x-confirm-submit></form>@endif
        @if($batch->status === 'returned_to_coordinator')<form method="POST" action="{{ route('coordinator.batches.submit-to-chairman', $batch) }}">@csrf<x-confirm-submit message="Send the Registrar results to the Chairman?">Submit to Chairman</x-confirm-submit></form>@endif
    </div>
</x-app-layout>
