<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $agency->agency_name }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __('Scholarship Masterlists') }}
                </h2>
            </div>
            <a href="{{ route('agency.masterlists.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                {{ __('Upload CSV') }}
            </a>
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
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('File') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Records') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Needs Review') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Uploaded') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($masterlists as $masterlist)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $masterlist->file_name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $masterlist->total_records }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ __(':duplicates duplicate, :invalid invalid', ['duplicates' => $masterlist->duplicate_count, 'invalid' => $masterlist->invalid_count]) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-md bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-900">
                                            {{ Str::headline($masterlist->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ optional($masterlist->uploaded_at ?? $masterlist->created_at)->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <a href="{{ route('agency.masterlists.show', $masterlist) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-600">
                                        {{ __('No masterlists uploaded yet.') }}
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
