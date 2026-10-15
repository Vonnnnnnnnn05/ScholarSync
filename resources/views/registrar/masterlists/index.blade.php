<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Campus Registrar') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Beneficiary Verification Queue') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-status-alerts/>

            <!-- External Grantee Rosters (Forwarded by Campus Coordinator) -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ __('External Agency Grantee Batches') }}</h3>
                        <p class="text-xs text-gray-500">{{ __('Agency beneficiary lists forwarded by your Campus Scholarship Coordinator for official enrollment verification.') }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                        {{ count($granteeBatches ?? []) }} {{ __('Batch(es)') }}
                    </span>
                </div>

                <div class="space-y-3">
                    @forelse($granteeBatches ?? [] as $gbatch)
                        <a class="block rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-emerald-500" href="{{ route('registrar.grantees.show', $gbatch) }}">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-bold text-gray-950">{{ $gbatch->agency_name ?: 'External Scholarship Agency' }}</h4>
                                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-mono font-medium text-emerald-800">Batch #{{ $gbatch->id }}</span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                        <span>📁 {{ $gbatch->original_file_name }}</span>
                                        <span>📅 Forwarded by Coordinator: {{ $gbatch->sent_to_registrar_at?->format('M d, Y h:i A') ?: 'Pending' }}</span>
                                    </div>
                                    @if($gbatch->coordinator_to_registrar_notes)
                                        <p class="mt-2 text-xs italic text-amber-800 bg-amber-50 rounded px-2.5 py-1 border border-amber-200">
                                            <strong>{{ __('Coordinator Notes:') }}</strong> {{ $gbatch->coordinator_to_registrar_notes }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-col sm:items-end gap-1.5">
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($gbatch->total_rows) }} {{ __('grantees') }}</span>
                                    @if($gbatch->status === 'sent_to_registrar')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 ring-1 ring-amber-600/20">
                                            ⏳ {{ __('Needs Registrar Verification') }}
                                        </span>
                                    @elseif($gbatch->status === 'verified_by_registrar')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                            ✓ {{ __('Verified & Returned to Coordinator') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-blue-600/20">
                                            ✓ {{ __('Endorsed to Chairman') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50/50 p-6 text-center text-sm text-gray-500">
                            {{ __('No external grantee lists have been forwarded to your campus registrar yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Standard Campus Masterlist Batches -->
            <div class="pt-4 border-t border-gray-100">
                <div class="mb-3">
                    <h3 class="text-base font-bold text-gray-900">{{ __('Campus Masterlist Batches') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse($batches as $batch)
                        <a class="block rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-emerald-500" href="{{ route('registrar.batches.show', $batch) }}">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-bold text-gray-950">{{ $batch->masterlist->program_name }}</h3>
                                    <div class="text-xs font-mono text-gray-500">{{ $batch->masterlist->file_name }}</div>
                                </div>
                                <span class="mt-2 text-sm font-medium text-gray-600 sm:mt-0">{{ $batch->records()->count() }} {{ __('records') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-lg bg-white p-6 text-center text-sm text-gray-600 shadow-sm ring-1 ring-gray-200">
                            {{ __('No standard campus masterlist batches are waiting for verification.') }}
                        </div>
                    @endforelse
                </div>
                @if ($batches->hasPages())
                    <div class="pt-2">
                        {{ $batches->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
