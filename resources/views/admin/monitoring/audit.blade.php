<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Central Monitoring') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Audit Trail') }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('User') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Subject') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Details') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($auditLogs as $log)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-950">{{ Str::headline($log->action) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $log->user?->name ?: __('System') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                                    <td class="max-w-md px-6 py-4 text-sm text-gray-700">{{ collect($log->metadata ?? [])->map(fn ($value, $key) => Str::headline($key).': '.$value)->implode(', ') ?: __('No details') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No audit entries yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($auditLogs->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">{{ $auditLogs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
