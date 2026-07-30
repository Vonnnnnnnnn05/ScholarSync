<x-app-layout>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Scholarships</p>
            <h1 class="text-2xl font-bold text-gray-950">Scholarship Details and Eligibility</h1>
            <p class="mt-1 text-sm text-gray-600">View agency scholarship details, requirements, guidelines, and deadlines.</p>
        </div>

        <section class="rounded-md border border-emerald-900/10 bg-white p-5 shadow-sm">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                <x-text-input name="search" class="w-full" :value="$search" placeholder="Search program, agency, fund source, or requirement" />
                <x-secondary-button class="min-h-11 justify-center">{{ __('Search') }}</x-secondary-button>
            </form>
        </section>

        <section class="grid gap-4">
            @forelse ($policies as $policy)
                <article class="rounded-md border border-emerald-900/10 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-950">{{ $policy->title }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $policy->agency->agency_name }} · {{ $policy->program?->fund_source ?? 'Fund source not specified' }}</p>
                        </div>
                        @if ($policy->deadline)
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-800">Deadline {{ $policy->deadline->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <p class="mt-4 text-sm leading-6 text-gray-700">{{ $policy->description ?: 'Details will be posted by the agency.' }}</p>
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-md bg-emerald-50 p-4">
                            <h3 class="font-semibold text-emerald-950">Eligibility Requirements</h3>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $policy->eligibility_requirements ?: 'No eligibility requirements posted yet.' }}</p>
                        </div>
                        <div class="rounded-md bg-gray-50 p-4">
                            <h3 class="font-semibold text-gray-950">Documentary Requirements</h3>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $policy->documentary_requirements ?: 'No documentary requirements posted yet.' }}</p>
                        </div>
                    </div>
                    @if ($policy->file_path)
                        <a href="{{ route('student.scholarships.download', $policy) }}" class="mt-5 inline-flex min-h-10 items-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900">Download Guidelines</a>
                    @endif
                </article>
            @empty
                <div class="rounded-md border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">No published scholarship details yet.</div>
            @endforelse
            {{ $policies->links() }}
        </section>
    </div>
</x-app-layout>
