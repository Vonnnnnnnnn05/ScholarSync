<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ $application->student->fullName() }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Evaluate Renewal Application') }}</h2>
            </div>
            <a href="{{ route('evaluator.scholarship-renewals.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ __('Back to Queue') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="lg:col-span-2 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Submitted Requirements') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($application->requirements as $requirement)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $requirement->requirement_name }}</td>
                                        <td class="px-6 py-4 text-right text-sm">
                                            <a href="{{ route('evaluator.scholarship-renewals.requirements.download', [$application, $requirement]) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('Download') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Evaluation') }}</h3>
                    <div class="mt-4 text-sm text-gray-700">
                        <p><span class="font-semibold">{{ __('Program:') }}</span> {{ $application->scholarship_program }}</p>
                        <p class="mt-1"><span class="font-semibold">{{ __('Current Status:') }}</span> {{ $application->status->label() }}</p>
                    </div>

                    <form method="POST" action="{{ route('evaluator.scholarship-renewals.update', $application) }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="status" :value="__('Evaluation Result')" />
                            <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                                @foreach ($evaluationStatuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $application->status->value) === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="remarks" :value="__('Remarks')" />
                            <textarea id="remarks" name="remarks" rows="5" class="mt-1 block w-full rounded-md border-emerald-900/20 bg-white text-sm text-slate-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('remarks', $application->remarks) }}</textarea>
                            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                        </div>

                        <x-primary-button>{{ __('Save Evaluation') }}</x-primary-button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
