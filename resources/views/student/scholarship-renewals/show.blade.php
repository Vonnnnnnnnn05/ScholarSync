<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('Continuing Scholarship') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $application->scholarship_program }}</h2>
            </div>
            <a href="{{ route('student.scholarship-renewals.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-700/20 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ __('Back to Applications') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
            @endif

            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-600">{{ __('Fund Source') }}</p>
                        <p class="mt-1 font-semibold text-gray-950">{{ $application->fund_source ?: __('Not specified') }}</p>
                    </div>
                    <span class="inline-flex w-fit rounded-md bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-900">{{ $application->status->label() }}</span>
                </div>
                <div class="mt-5">
                    <p class="text-sm text-gray-600">{{ __('Remarks') }}</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $application->remarks ?: __('No remarks yet.') }}</p>
                </div>
            </section>

            <section class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-950">{{ __('Uploaded Requirements') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($application->requirements as $requirement)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $requirement->requirement_name }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('student.scholarship-renewals.requirements.download', [$application, $requirement]) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ __('Download') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($application->canBeRevisedByStudent())
                <form method="POST" action="{{ route('student.scholarship-renewals.revise', $application) }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Resubmit Requirements') }}</h3>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="scholarship_program" :value="__('Scholarship Program')" />
                                <x-text-input id="scholarship_program" name="scholarship_program" class="mt-1 block w-full" :value="old('scholarship_program', $application->scholarship_program)" required />
                                <x-input-error :messages="$errors->get('scholarship_program')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="fund_source" :value="__('Fund Source')" />
                                <x-text-input id="fund_source" name="fund_source" class="mt-1 block w-full" :value="old('fund_source', $application->fund_source)" />
                                <x-input-error :messages="$errors->get('fund_source')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            @foreach ($requiredDocuments as $field => $label)
                                <div>
                                    <x-input-label :for="$field" :value="__($label)" />
                                    <input id="{{ $field }}" name="{{ $field }}" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block min-h-11 w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm text-slate-900 file:mr-4 file:rounded-md file:border-0 file:bg-emerald-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700" required>
                                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Resubmit Renewal') }}</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
