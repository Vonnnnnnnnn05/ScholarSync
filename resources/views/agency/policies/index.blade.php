<x-app-layout>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Scholarship Agency</p>
            <h1 class="text-2xl font-bold text-gray-950">Policies and Eligibility</h1>
            <p class="mt-1 text-sm text-gray-600">Publish scholarship rules, guidelines, qualification requirements, and downloadable policy files for students.</p>
        </div>

        <x-status-alerts />

        <section class="rounded-md border border-emerald-900/10 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-950">Create Scholarship Policy</h2>
            <form method="POST" action="{{ route('agency.policies.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-2">
                @csrf
                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="scholarship_program_id" :value="__('Scholarship Program')" />
                    <select id="scholarship_program_id" name="scholarship_program_id" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="">General agency policy</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('scholarship_program_id') == $program->id)>{{ $program->name }} - {{ $program->fund_source }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="description" :value="__('Scholarship Details')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-emerald-900/20 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('description') }}</textarea>
                </div>
                <div>
                    <x-input-label for="eligibility_requirements" :value="__('Eligibility Requirements')" />
                    <textarea id="eligibility_requirements" name="eligibility_requirements" rows="5" class="mt-1 block w-full rounded-md border-emerald-900/20 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('eligibility_requirements') }}</textarea>
                </div>
                <div>
                    <x-input-label for="documentary_requirements" :value="__('Documentary Requirements')" />
                    <textarea id="documentary_requirements" name="documentary_requirements" rows="5" class="mt-1 block w-full rounded-md border-emerald-900/20 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">{{ old('documentary_requirements') }}</textarea>
                </div>
                <div>
                    <x-input-label for="deadline" :value="__('Deadline')" />
                    <x-text-input id="deadline" name="deadline" type="date" class="mt-1 block w-full" :value="old('deadline')" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Visibility')" />
                    <select id="status" name="status" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="policy_file" :value="__('Policy File')" />
                    <input id="policy_file" name="policy_file" type="file" class="mt-1 block w-full rounded-md border border-emerald-900/20 bg-white px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100" />
                    <p class="mt-1 text-xs text-gray-500">Accepted: PDF, DOC, DOCX, JPG, PNG up to 5 MB.</p>
                </div>
                <div class="lg:col-span-2">
                    <x-primary-button>{{ __('Publish Policy') }}</x-primary-button>
                </div>
            </form>
        </section>

        <section class="grid gap-4">
            @forelse ($policies as $policy)
                <article class="rounded-md border border-emerald-900/10 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-950">{{ $policy->title }}</h2>
                            <p class="text-sm text-gray-600">{{ $policy->program?->name ?? 'General agency policy' }} @if($policy->deadline) · Deadline {{ $policy->deadline->format('M d, Y') }} @endif</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800">{{ $policy->status }}</span>
                    </div>
                    <p class="mt-3 text-sm text-gray-700">{{ $policy->description ?: 'No description provided.' }}</p>
                    @if ($policy->file_path)
                        <a href="{{ route('agency.policies.download', $policy) }}" class="mt-4 inline-flex min-h-10 items-center rounded-md border border-emerald-900/20 px-3 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-50">Download file</a>
                    @endif
                </article>
            @empty
                <div class="rounded-md border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500">No scholarship policies yet.</div>
            @endforelse
            {{ $policies->links() }}
        </section>
    </div>
</x-app-layout>
