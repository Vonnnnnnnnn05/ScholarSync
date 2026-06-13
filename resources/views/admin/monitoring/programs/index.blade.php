<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Central Monitoring') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Fund Source Monitoring') }}</h2>
            </div>
            <a href="{{ route('admin.monitoring.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50">{{ __('Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))<div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>@endif
            <form method="POST" action="{{ route('admin.monitoring.programs.store') }}" class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                @csrf
                <h3 class="text-base font-semibold text-gray-950">{{ __('Add Scholarship Program') }}</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <x-text-input name="name" placeholder="Program name" required />
                    <x-text-input name="fund_source" placeholder="Fund source" required />
                    <x-text-input name="agency_name" placeholder="Agency name" />
                    <select name="status" class="min-h-11 rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"><option value="active">{{ __('Active') }}</option><option value="inactive">{{ __('Inactive') }}</option></select>
                </div>
                <div class="mt-4"><x-primary-button>{{ __('Add Program') }}</x-primary-button></div>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Program') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Fund Source') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Agency') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Actions') }}</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($programs as $program)
                                <tr>
                                    <form method="POST" action="{{ route('admin.monitoring.programs.update', $program) }}">
                                        @csrf
                                        @method('PATCH')
                                        <td class="px-6 py-4"><x-text-input name="name" class="w-full" :value="$program->name" required /></td>
                                        <td class="px-6 py-4"><x-text-input name="fund_source" class="w-full" :value="$program->fund_source" required /></td>
                                        <td class="px-6 py-4"><x-text-input name="agency_name" class="w-full" :value="$program->agency_name" /></td>
                                        <td class="px-6 py-4"><select name="status" class="min-h-11 rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"><option value="active" @selected($program->status === 'active')>{{ __('Active') }}</option><option value="inactive" @selected($program->status === 'inactive')>{{ __('Inactive') }}</option></select></td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <button class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('Save') }}</button>
                                        </td>
                                    </form>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No scholarship programs yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($programs->hasPages())<div class="border-t border-gray-200 px-6 py-4">{{ $programs->links() }}</div>@endif
            </div>
        </div>
    </div>
</x-app-layout>
