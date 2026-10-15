@props(['batch'])

<div
    x-data="{
        isOpen: false,
        recordId: null,
        rowNumber: null,
        studentName: '',
        studentId: '',
        campus: '',
        conflictReason: '',
        candidates: [],
        selectedAction: 'confirm_match',
        selectedStudentId: null,
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        notes: '',

        init() {
            window.addEventListener('open-resolve-modal', (e) => {
                this.recordId = e.detail.recordId;
                this.rowNumber = e.detail.rowNumber;
                this.studentName = e.detail.name;
                this.studentId = e.detail.studentId;
                this.campus = e.detail.campus;
                this.conflictReason = e.detail.conflictReason;
                this.candidates = e.detail.candidates || [];
                this.selectedAction = 'confirm_match';
                this.selectedStudentId = this.candidates.length > 0 ? this.candidates[0].registrar_student_id : null;
                this.searchQuery = '';
                this.searchResults = [];
                this.notes = '';
                this.isOpen = true;
            });
        },

        async searchStudents() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            this.isSearching = true;
            try {
                const res = await fetch(`{{ route('chairman.grantees.students.search') }}?query=${encodeURIComponent(this.searchQuery)}`);
                this.searchResults = await res.json();
            } catch (err) {
                console.error(err);
            } finally {
                this.isSearching = false;
            }
        },

        selectSearched(st) {
            this.selectedStudentId = st.id;
            this.selectedAction = 'confirm_match';
            // Add to candidates if not present
            if (!this.candidates.some(c => c.registrar_student_id === st.id)) {
                this.candidates.push({
                    registrar_student_id: st.id,
                    student_name: st.student_name,
                    student_id_number: st.student_id_number,
                    campus_id: st.campus_id,
                    score: 1.0
                });
            }
            this.searchResults = [];
            this.searchQuery = '';
        }
    }"
    x-show="isOpen"
    x-cloak
    @keydown.escape.window="isOpen = false"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div x-show="isOpen" class="fixed inset-0 bg-gray-950/50 backdrop-blur-sm transition-opacity"></div>

    <div class="flex min-h-screen items-center justify-center p-4">
        <div
            x-show="isOpen"
            @click.away="isOpen = false"
            class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-gray-900/10 transition-all"
        >
            <!-- Header -->
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        {{ __('Row #') }}<span x-text="rowNumber"></span> &bull; {{ __('Resolution') }}
                    </span>
                    <h3 class="mt-2 text-lg font-bold text-gray-950" x-text="studentName"></h3>
                    <p class="text-xs text-gray-500">
                        {{ __('Original File Data — ID: ') }}<span class="font-mono" x-text="studentId || 'None'"></span> &bull;
                        {{ __('Campus: ') }}<span x-text="campus || 'Unspecified'"></span>
                    </p>
                </div>
                <button @click="isOpen = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Resolution Form -->
            <form
                method="POST"
                :action="`{{ url('chairman/grantees/' . $batch->id . '/records') }}/${recordId}/resolve`"
                class="mt-5 space-y-5"
            >
                @csrf
                @method('PATCH')

                <!-- Option: Pick Candidate Match -->
                <div class="space-y-3">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-600">
                        {{ __('Option 1: Confirm Official Student Match') }}
                    </label>

                    <div class="space-y-2 max-h-56 overflow-y-auto rounded-lg border border-gray-200 p-2">
                        <template x-for="cand in candidates" :key="cand.registrar_student_id">
                            <label class="flex cursor-pointer items-center justify-between rounded-lg border border-transparent p-3 hover:bg-gray-50 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/40">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        name="registrar_student_id"
                                        :value="cand.registrar_student_id"
                                        x-model="selectedStudentId"
                                        @change="selectedAction = 'confirm_match'"
                                        class="h-4 w-4 border-gray-300 text-emerald-700 focus:ring-emerald-700"
                                    >
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900" x-text="cand.student_name"></div>
                                        <div class="text-xs text-gray-500 font-mono" x-text="cand.student_id_number || 'No SIS ID'"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800" x-text="Math.round(cand.score * 100) + '% match'"></span>
                                </div>
                            </label>
                        </template>

                        <div x-show="candidates.length === 0" class="py-4 text-center text-xs text-gray-500">
                            {{ __('No automated candidate matches found.') }}
                        </div>
                    </div>
                </div>

                <!-- Option: Search SIS Manually -->
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-600">
                        {{ __('Search SIS Student Database (Manual Lookup)') }}
                    </label>
                    <div class="relative mt-1">
                        <input
                            type="text"
                            x-model="searchQuery"
                            @input.debounce.300ms="searchStudents()"
                            placeholder="{{ __('Type student name or ID number...') }}"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                        >
                        <div x-show="isSearching" class="absolute right-3 top-2.5 text-xs text-gray-400">
                            {{ __('Searching...') }}
                        </div>
                    </div>

                    <!-- Search Results dropdown -->
                    <div x-show="searchResults.length > 0" class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                        <template x-for="st in searchResults" :key="st.id">
                            <div
                                @click="selectSearched(st)"
                                class="flex cursor-pointer items-center justify-between p-2.5 hover:bg-emerald-50 text-xs"
                            >
                                <div>
                                    <span class="font-semibold text-gray-900" x-text="st.student_name"></span>
                                    <span class="ml-2 font-mono text-gray-500" x-text="st.student_id_number"></span>
                                </div>
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-600" x-text="st.campus ? st.campus.name : ''"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Option: Mark as Unmatched -->
                <div class="rounded-lg border border-dashed border-rose-300 bg-rose-50/30 p-3">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="radio"
                            name="action"
                            value="mark_unmatched"
                            x-model="selectedAction"
                            class="h-4 w-4 border-gray-300 text-rose-600 focus:ring-rose-600"
                        >
                        <div>
                            <span class="text-sm font-semibold text-rose-900">{{ __('Mark as Unmatched (Red)') }}</span>
                            <p class="text-xs text-rose-700">{{ __('Record is invalid or not an enrolled student. Will be excluded from commit.') }}</p>
                        </div>
                    </label>
                </div>

                <!-- Hidden Action input when confirm_match selected -->
                <input type="hidden" name="action" :value="selectedAction">

                <!-- Resolution Notes -->
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-600">
                        {{ __('Chairman Resolution Notes') }}
                    </label>
                    <textarea
                        name="resolution_notes"
                        x-model="notes"
                        rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                        placeholder="{{ __('Optional: specify why this match was selected or overridden...') }}"
                    ></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-800 px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                    >
                        {{ __('Confirm Resolution') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
