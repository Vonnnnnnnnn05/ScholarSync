<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('ScholarSync') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __($title) }}
                </h2>
            </div>
            <span class="inline-flex w-fit rounded-md bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800 ring-1 ring-emerald-700/15">
                {{ __($role->label()) }}
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-l-4 border-emerald-700 p-6 sm:p-8">
                    <p class="text-sm font-semibold uppercase text-emerald-700">
                        {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}
                    </p>
                    <h3 class="mt-3 max-w-3xl text-2xl font-semibold text-gray-950">
                        {{ __($summary) }}
                    </h3>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
                        {{ __('This dashboard is tailored to your account role. More workflow tools can be added here as the next phases are completed.') }}
                    </p>
                </div>
            </section>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($items as $item)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <div class="flex h-10 w-10 items-center justify-center rounded-md bg-yellow-100 text-sm font-bold text-emerald-900 ring-1 ring-yellow-300">
                            {{ $loop->iteration }}
                        </div>
                        <p class="mt-4 text-sm font-semibold text-gray-950">{{ __($item) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
