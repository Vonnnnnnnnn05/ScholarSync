<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Coordinator') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                {{ __('Masterlist Validation') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Agency') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('File') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Summary') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Coordinator Progress') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($masterlists as $masterlist)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $masterlist->agency->agency_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $masterlist->file_name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ __(':enrolled enrolled, :unenrolled unenrolled, :duplicates duplicate, :invalid invalid', [
                                            'enrolled' => $masterlist->enrolled_count,
                                            'unenrolled' => $masterlist->unenrolled_count,
                                            'duplicates' => $masterlist->duplicate_count,
                                            'invalid' => $masterlist->invalid_count,
                                        ]) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ __(':reviewed reviewed, :pending pending', [
                                            'reviewed' => $masterlist->reviewed_records_count,
                                            'pending' => $masterlist->pending_records_count,
                                        ]) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-900">
                                            {{ Str::headline($masterlist->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('coordinator.masterlists.show', $masterlist) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">
                                            {{ __('Review') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No verified masterlists are waiting for coordinator validation.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($masterlists->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $masterlists->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
