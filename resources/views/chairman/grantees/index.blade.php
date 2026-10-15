<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Grantee Ingestion Batches') }}
                </h2>
            </div>
            <a href="{{ route('chairman.grantees.upload') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Upload Masterlist File') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Status Messages -->
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Batch Table -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-600">
                            <tr>
                                <th class="px-6 py-3.5 text-left">{{ __('Batch / Program') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Period') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('File & Rows') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Verification Health') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Lifecycle Status') }}</th>
                                <th class="px-6 py-3.5 text-left">{{ __('Uploaded') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($batches as $batch)
                                <tr class="hover:bg-gray-50/75 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-950 flex items-center gap-1.5 flex-wrap">
                                            <span>{{ $batch->scholarship_name }}</span>
                                            @if($batch->campus)
                                                <span class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 border border-blue-200">
                                                    {{ $batch->campus->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $batch->agency_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-700">
                                        <div class="font-semibold">{{ $batch->academic_year }}</div>
                                        <div class="text-gray-500">{{ $batch->semester }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <span class="inline-flex items-center gap-1 font-mono font-bold text-gray-900">
                                            {{ number_format($batch->total_rows) }} {{ __('rows') }}
                                        </span>
                                        <div class="text-xs uppercase text-gray-400 font-semibold">{{ $batch->file_type }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1.5 text-xs font-bold">
                                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-emerald-800" title="Green (Verified)">{{ $batch->green_count }}G</span>
                                            <span class="rounded bg-amber-100 px-2 py-0.5 text-amber-800" title="Amber (Conflicts)">{{ $batch->amber_count }}A</span>
                                            <span class="rounded bg-rose-100 px-2 py-0.5 text-rose-800" title="Red (Unmatched)">{{ $batch->red_count }}R</span>
                                            @if($batch->pending_count > 0)
                                                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-600" title="Pending">{{ $batch->pending_count }}P</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs">
                                        @if($batch->status === 'committed')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 font-bold text-emerald-800">
                                                <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                {{ __('Committed') }}
                                            </span>
                                        @elseif($batch->status === 'verified')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 font-bold text-blue-800">
                                                {{ __('Verified') }}
                                            </span>
                                        @elseif($batch->status === 'verifying')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 font-bold text-amber-800 animate-pulse">
                                                {{ __('Verifying...') }}
                                            </span>
                                        @elseif($batch->status === 'failed')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 font-bold text-rose-800">
                                                {{ __('Failed') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 font-bold text-gray-700">
                                                {{ __('Staged') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-500">
                                        <div>{{ $batch->created_at->format('M d, Y') }}</div>
                                        <div class="text-[11px] text-gray-400">{{ $batch->uploader?->name ?? 'System' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-xs">
                                        <a href="{{ route('chairman.grantees.show', $batch) }}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 font-bold text-emerald-800 hover:bg-emerald-100">
                                            {{ __('Reconcile') }}
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="mt-3 text-sm font-semibold text-gray-900">{{ __('No grantee batches staged yet.') }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ __('Upload your first external agency masterlist to begin verification.') }}</p>
                                        <a href="{{ route('chairman.grantees.upload') }}" class="mt-4 inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                            {{ __('Upload Now') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($batches->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $batches->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
