<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-medium text-emerald-700">Campus Scholarship Coordinator</p><h2 class="text-xl font-semibold">Beneficiary Campus Batches</h2></div></x-slot>
    <div class="py-10"><div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8"><x-status-alerts/>
        <div class="overflow-x-auto rounded-lg bg-white shadow"><table class="min-w-full"><thead><tr class="border-b"><th class="p-4 text-left">Agency</th><th class="p-4 text-left">File</th><th class="p-4 text-left">Records</th><th class="p-4 text-left">Status</th><th class="p-4 text-right">Action</th></tr></thead><tbody>
        @forelse($batches as $batch)<tr class="border-b"><td class="p-4">{{ $batch->masterlist->agency->agency_name }}</td><td class="p-4">{{ $batch->masterlist->file_name }}</td><td class="p-4">{{ $batch->records()->count() }}</td><td class="p-4">{{ Str::headline($batch->status) }}</td><td class="p-4 text-right"><a class="font-semibold text-emerald-800" href="{{ route('coordinator.batches.show', $batch) }}">Review</a></td></tr>
        @empty<tr><td colspan="5" class="p-8 text-center text-gray-600">No beneficiary batches are assigned to this campus.</td></tr>@endforelse
        </tbody></table></div>{{ $batches->links() }}
    </div></div>
</x-app-layout>
