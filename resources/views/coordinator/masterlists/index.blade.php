<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Campus Scholarship Coordinator') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">{{ __('Beneficiary Campus Batches') }}</h2>
            </div>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-status-alerts/>
            <!-- External Agency Grantee Ingestion Batches (CHED, UniFAST, TDP, CMSP) -->
            @if(isset($granteeBatches) && $granteeBatches->isNotEmpty())
                <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">✓</span>
                                {{ __('Agency Grantee Batches Delivered to Campus') }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ __('External scholarship lists routed by the Scholarship Chairman for coordinator review and endorsement.') }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                            <thead class="bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">{{ __('Agency & File Name') }}</th>
                                    <th class="px-4 py-3">{{ __('Campus Destination') }}</th>
                                    <th class="px-4 py-3">{{ __('Total Records') }}</th>
                                    <th class="px-4 py-3">{{ __('Verification Overview') }}</th>
                                    <th class="px-4 py-3">{{ __('Delivery & Endorsement Status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($granteeBatches as $gb)
                                    <tr class="hover:bg-gray-50/75">
                                        <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-400">#{{ $gb->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-950">{{ $gb->agency_name ?? 'Agency' }} ({{ $gb->scholarship_name ?? 'Auto-Detected' }})</div>
                                            <div class="text-[11px] font-mono text-gray-400 truncate max-w-xs">{{ $gb->original_file_name }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-800">
                                            <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800 border border-blue-200">
                                                {{ $gb->campus?->name ?? 'Campus' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-gray-900">{{ $gb->total_rows }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-200">
                                                    {{ $gb->green_count }} Verified
                                                </span>
                                                @if($gb->amber_count > 0)
                                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800 border border-amber-200">
                                                        {{ $gb->amber_count }} Conflicts
                                                    </span>
                                                @endif
                                                @if($gb->pending_count > 0)
                                                    <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                                                        {{ $gb->pending_count }} Pending
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($gb->coordinator_endorsed_at)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-900 border border-emerald-300">
                                                    ✓ {{ __('Endorsed to Chairman') }}
                                                </span>
                                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $gb->coordinator_endorsed_at->format('M d, Y') }}</div>
                                            @elseif($gb->delivered_to_coordinator_at)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-bold text-blue-900 border border-blue-300">
                                                    {{ __('Delivered by Chairman') }}
                                                </span>
                                                <div class="text-[10px] text-gray-400 mt-0.5">{{ $gb->delivered_to_coordinator_at->format('M d, Y') }}</div>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-medium text-gray-700">
                                                    {{ __('Assigned to Campus') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a href="{{ route('coordinator.grantees.show', $gb) }}" class="inline-flex items-center gap-1 rounded-xl bg-emerald-800 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-700">
                                                <span>{{ __('Review & Endorse') }}</span>
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Legacy Masterlist Campus Batches -->
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Scholarship Program / File') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Campus') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Records') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($batches as $batch)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-950">{{ $batch->masterlist->program_name }}</div>
                                    <div class="text-xs font-mono text-gray-500">{{ $batch->masterlist->file_name }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $batch->campus->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $batch->records()->count() }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-md bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-900">
                                        {{ Str::headline($batch->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a class="font-semibold text-emerald-800 hover:text-emerald-950" href="{{ route('coordinator.batches.show', $batch) }}">Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No legacy beneficiary batches are assigned to this campus.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($batches->hasPages())
                <div class="pt-2">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
