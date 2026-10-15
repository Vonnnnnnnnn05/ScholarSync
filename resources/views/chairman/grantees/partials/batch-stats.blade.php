@props(['batch'])

@php
    $total = $batch->total_rows ?: 1;
    $greenPct = round(($batch->green_count / $total) * 100);
    $amberPct = round(($batch->amber_count / $total) * 100);
    $redPct = round(($batch->red_count / $total) * 100);
    $pendingPct = round(($batch->pending_count / $total) * 100);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Total Records -->
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ __('Total Rows') }}</span>
            <span class="rounded-full bg-gray-100 p-2 text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-gray-950">{{ number_format($batch->total_rows) }}</span>
            <span class="text-xs font-semibold uppercase text-gray-500">{{ $batch->file_type }}</span>
        </div>
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
            <span>{{ __('Batch #') }}{{ $batch->id }}</span>
            <span>&bull;</span>
            <span class="capitalize">{{ $batch->status }}</span>
        </div>
    </div>

    <!-- Green Verified -->
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">{{ __('Verified (Green)') }}</span>
            <span class="rounded-full bg-emerald-100 p-2 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-emerald-950">{{ number_format($batch->green_count) }}</span>
            <span class="text-xs font-bold text-emerald-700">{{ $greenPct }}%</span>
        </div>
        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-emerald-200">
            <div class="h-full bg-emerald-600 rounded-full" style="width: {{ $greenPct }}%"></div>
        </div>
    </div>

    <!-- Amber Conflicts -->
    <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-800">{{ __('Conflicts (Amber)') }}</span>
            <span class="rounded-full bg-amber-100 p-2 text-amber-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-amber-950">{{ number_format($batch->amber_count) }}</span>
            <span class="text-xs font-bold text-amber-700">{{ $amberPct }}%</span>
        </div>
        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-amber-200">
            <div class="h-full bg-amber-600 rounded-full" style="width: {{ $amberPct }}%"></div>
        </div>
    </div>

    <!-- Red Unmatched -->
    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-800">{{ __('Unmatched (Red)') }}</span>
            <span class="rounded-full bg-rose-100 p-2 text-rose-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </span>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-rose-950">{{ number_format($batch->red_count) }}</span>
            <span class="text-xs font-bold text-rose-700">{{ $redPct }}%</span>
        </div>
        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-rose-200">
            <div class="h-full bg-rose-600 rounded-full" style="width: {{ $redPct }}%"></div>
        </div>
    </div>
</div>
