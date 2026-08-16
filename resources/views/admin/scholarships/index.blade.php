<x-app-layout>
    <x-slot name="header">
        <div><p class="text-sm font-medium text-emerald-700">Scholarships</p><h2 class="text-xl font-semibold text-gray-900">Scholarship Opportunity Management</h2></div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-status-alerts />

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900">Create Scholarship Opportunity</h3>
            <p class="mt-1 text-sm text-gray-600">Enter the agency and program together with the official opportunity information.</p>
            <form method="POST" action="{{ route('admin.scholarships.store') }}" class="mt-5 grid gap-4 lg:grid-cols-2">@csrf
                <div><x-input-label for="agency" value="Scholarship Agency" /><x-text-input id="agency" name="agency" class="mt-1 w-full" :value="old('agency')" placeholder="e.g. CHED" required /><x-input-error :messages="$errors->get('agency')" class="mt-2" /></div>
                <div><x-input-label for="program" value="Scholarship Program" /><x-text-input id="program" name="program" class="mt-1 w-full" :value="old('program')" placeholder="e.g. Tulong Dunong Program" required /><x-input-error :messages="$errors->get('program')" class="mt-2" /></div>
                <div><x-input-label for="title" value="Opportunity Title" /><x-text-input id="title" name="title" class="mt-1 w-full" :value="old('title')" required /></div>
                <div><x-input-label for="deadline" value="Application Deadline" /><x-text-input id="deadline" name="deadline" type="date" class="mt-1 w-full" :value="old('deadline')" /></div>
                <div class="lg:col-span-2"><x-input-label for="application_link" value="Official Application Link" /><x-text-input id="application_link" name="application_link" type="url" class="mt-1 w-full" :value="old('application_link')" required /></div>
                <div class="lg:col-span-2"><x-input-label for="description" value="Description" /><textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('description') }}</textarea></div>
                <div><x-input-label for="eligibility_requirements" value="Qualifications" /><textarea id="eligibility_requirements" name="eligibility_requirements" rows="4" class="mt-1 w-full rounded-md border-gray-300">{{ old('eligibility_requirements') }}</textarea></div>
                <div><x-input-label for="documentary_requirements" value="Requirements and Guidelines" /><textarea id="documentary_requirements" name="documentary_requirements" rows="4" class="mt-1 w-full rounded-md border-gray-300">{{ old('documentary_requirements') }}</textarea></div>
                <div><x-input-label for="status" value="Status" /><select id="status" name="status" class="mt-1 min-h-11 w-full rounded-md border-gray-300"><option value="published" @selected(old('status') === 'published')>Published / Active</option><option value="draft" @selected(old('status') === 'draft')>Draft</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option><option value="archived" @selected(old('status') === 'archived')>Archived</option></select></div>
                <div class="flex items-end justify-end"><x-primary-button>Save Opportunity</x-primary-button></div>
            </form>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6">
            <div class="mb-4"><h3 class="text-lg font-semibold text-gray-900">Saved Opportunities</h3><p class="mt-1 text-sm text-gray-600">View, edit, or delete an existing scholarship opportunity.</p></div>
            <div class="space-y-3">@forelse($policies as $policy)
                <article x-data="{ viewOpen: false, editOpen: false, deleteOpen: false }" class="rounded-lg border border-gray-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><h4 class="font-semibold text-gray-900">{{ $policy->title }}</h4><p class="mt-1 text-sm text-gray-600">{{ $policy->agency->agency_name }} · {{ $policy->program?->name }} · {{ str($policy->status)->headline() }}</p></div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" x-on:click="viewOpen = true" aria-label="View opportunity" title="View" class="flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 text-emerald-800 hover:bg-emerald-50"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                            <button type="button" x-on:click="editOpen = true" aria-label="Edit opportunity" title="Edit" class="flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 text-blue-700 hover:bg-blue-50"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button>
                            <button type="button" x-on:click="deleteOpen = true" aria-label="Delete opportunity" title="Delete" class="flex h-9 w-9 items-center justify-center rounded-md border border-red-200 text-red-700 hover:bg-red-50"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <div x-cloak x-show="viewOpen" x-on:keydown.escape.window="viewOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="view-opportunity-{{ $policy->id }}-title">
                        <div class="absolute inset-0 bg-gray-950/50" x-on:click="viewOpen = false" aria-hidden="true"></div>
                        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                            <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4"><div><h3 id="view-opportunity-{{ $policy->id }}-title" class="text-lg font-semibold text-gray-950">{{ $policy->title }}</h3><p class="mt-1 text-sm text-gray-600">{{ $policy->agency->agency_name }} · {{ $policy->program?->name }}</p></div><button type="button" x-on:click="viewOpen = false" aria-label="Close view" class="flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
                            <div class="mt-5 grid gap-5 text-sm md:grid-cols-2"><div><b>Description</b><p class="mt-1 whitespace-pre-line text-gray-700">{{ $policy->description ?: 'Not provided' }}</p></div><div><b>Deadline</b><p class="mt-1 text-gray-700">{{ $policy->deadline?->format('M d, Y') ?: 'No deadline' }}</p></div><div><b>Qualifications</b><p class="mt-1 whitespace-pre-line text-gray-700">{{ $policy->eligibility_requirements ?: 'Not provided' }}</p></div><div><b>Requirements and Guidelines</b><p class="mt-1 whitespace-pre-line text-gray-700">{{ $policy->documentary_requirements ?: 'Not provided' }}</p></div><div><b>Status</b><p class="mt-1 text-gray-700">{{ str($policy->status)->headline() }}</p></div><div class="md:col-span-2"><b>Official Application Link</b><p class="mt-1 break-all"><a href="{{ $policy->application_link }}" target="_blank" rel="noopener" class="text-emerald-700 underline">{{ $policy->application_link }}</a></p></div></div>
                            <div class="mt-6 flex justify-end"><button type="button" x-on:click="viewOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Close</button></div>
                        </div>
                    </div>

                    <div x-cloak x-show="editOpen" x-on:keydown.escape.window="editOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="edit-opportunity-{{ $policy->id }}-title">
                        <div class="absolute inset-0 bg-gray-950/50" x-on:click="editOpen = false" aria-hidden="true"></div>
                        <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                            <div class="border-b border-gray-200 pb-4"><h3 id="edit-opportunity-{{ $policy->id }}-title" class="text-lg font-semibold text-gray-950">Edit Scholarship Opportunity</h3><p class="mt-1 text-sm text-gray-600">Update the opportunity information and publishing status.</p></div>
                            <form method="POST" action="{{ route('admin.scholarships.update', $policy) }}" class="mt-5 grid gap-4 lg:grid-cols-2">@csrf @method('PATCH')
                                <div><x-input-label :for="'agency_'.$policy->id" value="Scholarship Agency" /><x-text-input :id="'agency_'.$policy->id" name="agency" class="mt-1 w-full" :value="$policy->agency->agency_name" required /></div>
                                <div><x-input-label :for="'program_'.$policy->id" value="Scholarship Program" /><x-text-input :id="'program_'.$policy->id" name="program" class="mt-1 w-full" :value="$policy->program?->name" required /></div>
                                <div><x-input-label :for="'title_'.$policy->id" value="Opportunity Title" /><x-text-input :id="'title_'.$policy->id" name="title" class="mt-1 w-full" :value="$policy->title" required /></div>
                                <div><x-input-label :for="'deadline_'.$policy->id" value="Application Deadline" /><x-text-input :id="'deadline_'.$policy->id" name="deadline" type="date" class="mt-1 w-full" :value="$policy->deadline?->format('Y-m-d')" /></div>
                                <div class="lg:col-span-2"><x-input-label :for="'link_'.$policy->id" value="Official Application Link" /><x-text-input :id="'link_'.$policy->id" name="application_link" type="url" class="mt-1 w-full" :value="$policy->application_link" required /></div>
                                <div class="lg:col-span-2"><x-input-label :for="'description_'.$policy->id" value="Description" /><textarea id="description_{{ $policy->id }}" name="description" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ $policy->description }}</textarea></div>
                                <div><x-input-label :for="'eligibility_'.$policy->id" value="Qualifications" /><textarea id="eligibility_{{ $policy->id }}" name="eligibility_requirements" rows="4" class="mt-1 w-full rounded-md border-gray-300">{{ $policy->eligibility_requirements }}</textarea></div>
                                <div><x-input-label :for="'requirements_'.$policy->id" value="Requirements and Guidelines" /><textarea id="requirements_{{ $policy->id }}" name="documentary_requirements" rows="4" class="mt-1 w-full rounded-md border-gray-300">{{ $policy->documentary_requirements }}</textarea></div>
                                <div><x-input-label :for="'status_'.$policy->id" value="Status" /><select id="status_{{ $policy->id }}" name="status" class="mt-1 min-h-11 w-full rounded-md border-gray-300"><option value="published" @selected($policy->status === 'published')>Published / Active</option><option value="draft" @selected($policy->status === 'draft')>Draft</option><option value="inactive" @selected($policy->status === 'inactive')>Inactive</option><option value="archived" @selected($policy->status === 'archived')>Archived</option></select></div>
                                <div class="flex items-end justify-end gap-3"><button type="button" x-on:click="editOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><x-primary-button>Save Changes</x-primary-button></div>
                            </form>
                        </div>
                    </div>

                    <div x-cloak x-show="deleteOpen" x-on:keydown.escape.window="deleteOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-opportunity-{{ $policy->id }}-title">
                        <div class="absolute inset-0 bg-gray-950/50" x-on:click="deleteOpen = false" aria-hidden="true"></div>
                        <div class="relative w-full max-w-md rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                            <h3 id="delete-opportunity-{{ $policy->id }}-title" class="text-lg font-semibold text-gray-950">Delete Scholarship Opportunity?</h3>
                            <p class="mt-2 text-sm leading-6 text-gray-600">This will permanently delete <strong>{{ $policy->title }}</strong>. The related agency and program records will be preserved.</p>
                            <form method="POST" action="{{ route('admin.scholarships.destroy', $policy) }}" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">@csrf @method('DELETE')<button type="button" x-on:click="deleteOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Delete</button></form>
                        </div>
                    </div>
                </article>
            @empty <p class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">No scholarship opportunities yet.</p> @endforelse</div>
            <div class="mt-5">{{ $policies->links() }}</div>
        </section>
    </div></div>
</x-app-layout>
