<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Scholarship Chairman') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Consolidate') }} {{ $masterlist->program_name }}
                </h2>
                <p class="text-xs font-mono text-gray-500">{{ $masterlist->file_name }}</p>
            </div>
            <a href="{{ route('chairman.masterlists.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-700">
                {{ __('Back to Progress') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-status-alerts/>
            @if($errors->has('release'))
                <p class="rounded-md border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-900">{{ $errors->first('release') }}</p>
            @endif

            <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach($campusProgress as $item)
                    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-sm font-bold text-gray-950">{{ $item['campus']->name }}</h3>
                        <p class="mt-1 text-xs text-gray-500">{{ $item['records'] }} {{ __('records') }}</p>
                        <p class="mt-2 text-sm font-semibold text-emerald-800">{{ Str::headline($item['status']) }}</p>
                    </article>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-base font-bold text-gray-950">{{ __('Final Qualified Beneficiaries') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Beneficiary') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Campus') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('Enrollment') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">{{ __('COR') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($masterlist->records()->with('campus')->where('final_qualification_status', 'qualified')->oldest('id')->get() as $record)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $record->student_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-800">{{ $record->campus?->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ Str::headline($record->final_enrollment_status) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ Str::headline($record->final_cor_status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No beneficiaries qualified.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-4">
                @if(in_array($masterlist->status, ['ready_for_consolidation', 'released'], true))
                    <a class="inline-flex min-h-10 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2" href="{{ route('chairman.masterlists.export', $masterlist) }}">
                        {{ __('Export Final Qualified CSV') }}
                    </a>
                @endif
                @if($masterlist->status !== 'released')
                    <form method="POST" action="{{ route('chairman.masterlists.release', $masterlist) }}">
                        @csrf
                        <x-confirm-submit message="Mark this verified list as forwarded to the external Scholarship Agency?">{{ __('Mark as Forwarded') }}</x-confirm-submit>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
