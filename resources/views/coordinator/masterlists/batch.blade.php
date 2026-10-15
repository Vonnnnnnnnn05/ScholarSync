<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $batch->campus->name }} · {{ __('Campus Coordinator') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ $batch->masterlist->program_name }}
                </h2>
                <p class="text-xs font-mono text-gray-500">{{ $batch->masterlist->file_name }} <span class="font-sans font-normal text-gray-500">· {{ Str::headline($batch->status) }}</span></p>
            </div>
            <a href="{{ route('coordinator.masterlists.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                {{ __('Back to Batches') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-status-alerts />
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Beneficiary') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Enrollment') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('COR') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Qualification') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Result Message') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($records as $record)
                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $record->student_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ Str::headline($record->final_enrollment_status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ Str::headline($record->final_cor_status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ Str::headline($record->final_qualification_status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $record->remarks ?: $record->automatic_result_message ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($batch->status === 'verification_failed')
                <form method="POST" action="{{ route('coordinator.batches.retry-verification', $batch) }}">
                    @csrf
                    <x-confirm-submit message="Retry failed automatic verification records?">Retry Verification</x-confirm-submit>
                </form>
            @endif
            @if ($records->hasPages())
                <div class="pt-2">
                    {{ $records->links() }}
                </div>
            @endif
            @if($batch->status === 'with_coordinator')
                <form method="POST" action="{{ route('coordinator.batches.submit-to-registrar', $batch) }}">
                    @csrf
                    <x-confirm-submit message="Submit this campus batch to the Registrar?">Submit to Registrar</x-confirm-submit>
                </form>
            @endif
            @if($batch->status === 'returned_to_coordinator')
                <form method="POST" action="{{ route('coordinator.batches.submit-to-chairman', $batch) }}">
                    @csrf
                    <x-confirm-submit message="Send the Registrar results to the Chairman?">Submit to Chairman</x-confirm-submit>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

