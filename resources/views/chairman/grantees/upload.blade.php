<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('Grantee Ingestion Pipeline') }}</p>
                <h2 class="text-xl font-bold leading-tight text-gray-900">
                    {{ __('Upload External Agency Masterlist') }}
                </h2>
            </div>
            <a href="{{ route('chairman.grantees.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('View Upload Batches') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        files: [],
        fileNames: [],
        totalSizeStr: '',
        isParsing: false,
        parseError: null,
        previewData: null,
        dragOver: false,
        selectedCampusId: '',
        overrideAllFlag: true,
        campusesList: @js($campuses),

        applyCampusToAll(overrideAll = true) {
            this.overrideAllFlag = overrideAll;
            if (!this.selectedCampusId || !this.previewData) return;
            const campusObj = this.campusesList.find(c => String(c.id) === String(this.selectedCampusId));
            const campusName = campusObj ? campusObj.name : '';

            const updateRow = (r) => {
                if (overrideAll || !r.campus) {
                    r.campus = campusName;
                    r.campus_id = parseInt(this.selectedCampusId);
                    r.remarks = 'Delivered to ' + campusName + ' Coordinator';
                }
            };

            (this.previewData.preview_rows || []).forEach(updateRow);
            (this.previewData.all_rows || []).forEach(updateRow);
            if (overrideAll) {
                this.previewData.unmatched_count = 0;
            } else {
                this.previewData.unmatched_count = (this.previewData.all_rows || []).filter(r => !r.campus).length;
            }
        },

        handleFileSelect(e) {
            const selected = Array.from(e.target.files || e.dataTransfer.files || []);
            if (selected.length > 0) {
                this.files = selected;
                this.fileNames = selected.map(f => f.name);
                const totalBytes = selected.reduce((acc, f) => acc + f.size, 0);
                this.totalSizeStr = (totalBytes / 1024 / 1024).toFixed(2) + ' MB';
                this.uploadAndParse();
            }
        },

        async uploadAndParse() {
            if (!this.files.length) return;
            this.isParsing = true;
            this.parseError = null;
            this.previewData = null;

            const formData = new FormData();
            if (this.files.length === 1) {
                formData.append('file', this.files[0]);
            }
            this.files.forEach(f => {
                formData.append('files[]', f);
            });
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const res = await fetch('{{ route('chairman.grantees.upload.parse') }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.parseError = data.error || data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to parse file.');
                } else {
                    this.previewData = data.data;
                    if (this.selectedCampusId) {
                        this.applyCampusToAll(false);
                    }
                }
            } catch (err) {
                this.parseError = 'Network error or microservice unreachable. Please ensure the Python verifier service is running on port 8001.';
            } finally {
                this.isParsing = false;
            }
        }
    }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Error -->
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-rose-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-rose-800">{{ __('Please address the following errors:') }}</h3>
                            <ul class="mt-1 list-disc pl-5 text-xs text-rose-700">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 1: File Upload Card -->
            <form method="POST" action="{{ route('chairman.grantees.upload.store') }}" class="space-y-6">
                @csrf

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">1</span>
                        {{ __('Masterlist File Selection & Upload') }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Upload an official beneficiary masterlist provided by CHED, UniFAST, LGU, or external agencies (PDF, XLSX, CSV).') }}
                    </p>

                    <!-- Dynamic Program Auto-Detection Notice -->
                    <div class="mt-4 flex items-center gap-2.5 rounded-xl bg-emerald-50/90 px-4 py-3 text-xs text-emerald-900 border border-emerald-200 shadow-xs">
                        <svg class="h-4 w-4 flex-shrink-0 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            <strong class="font-bold text-emerald-950">{{ __('Dynamic Category Parser Active:') }}</strong>
                            {{ __('Internal banners (TES-UniFAST, TDP, CMSP, DOST, LGU) and spreadsheet columns are automatically detected and assigned per student. All scholarship types are fully cross-verified against the official SIS masterlist.') }}
                        </span>
                    </div>

                    <!-- Target Campus Destination Pre-Selector (Optional) -->
                    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <div>
                            <x-input-label for="pre_campus_id" :value="__('Campus Destination (Sets ALL records at once)')" class="text-xs font-bold text-gray-800" />
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                {{ __('Optional: Choose a campus destination to route ALL records at once (not 1 by 1), or leave blank to auto-detect from SIS.') }}
                            </p>
                        </div>
                        <select
                            id="pre_campus_id"
                            x-model="selectedCampusId"
                            @change="applyCampusToAll(true)"
                            class="w-full sm:w-72 rounded-xl border-gray-300 bg-white py-2 px-3 text-xs font-semibold text-gray-800 shadow-xs focus:border-emerald-700 focus:ring-emerald-700"
                        >
                            <option value="">{{ __('Auto-detect from SIS Roster...') }}</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Drag-and-drop zone -->
                    <div class="mt-5">
                        <x-input-label :value="__('Masterlist File (.pdf, .xlsx, .csv)')" />
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="dragOver = false; handleFileSelect($event)"
                            :class="dragOver ? 'border-emerald-600 bg-emerald-50/50' : 'border-gray-300 bg-gray-50/50 hover:bg-gray-50'"
                            class="mt-2 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-8 text-center transition cursor-pointer relative"
                        >
                            <input
                                type="file"
                                id="masterlist_file"
                                name="files[]"
                                multiple
                                accept=".pdf,.xlsx,.xls,.csv"
                                @change="handleFileSelect($event)"
                                class="absolute inset-0 h-full w-full opacity-0 cursor-pointer"
                            >

                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-800">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>

                            <div class="mt-4">
                                <p class="text-sm font-semibold text-gray-900">
                                    <span class="text-emerald-800 underline">{{ __('Click to browse') }}</span> {{ __('or drag and drop 1 or more agency files here') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ __('Supported formats: PDF (multi-page, multi-column tables), Excel (.xlsx, .xls), CSV up to 20MB per file') }}
                                </p>
                            </div>

                            <div x-show="fileNames.length > 0" class="mt-4 flex flex-col items-center gap-2">
                                <div class="flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-gray-200">
                                    <span class="font-bold text-emerald-800" x-text="fileNames.length + (fileNames.length === 1 ? ' file selected' : ' files selected')"></span>
                                    <span class="text-gray-400" x-text="'(' + totalSizeStr + ')'"></span>
                                </div>
                                <div class="flex flex-wrap justify-center gap-1.5 max-w-lg">
                                    <template x-for="name in fileNames" :key="name">
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 border border-emerald-200" x-text="name"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parsing Loading Indicator -->
                    <div x-show="isParsing" class="mt-4 flex items-center justify-center gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-900">
                        <svg class="h-5 w-5 animate-spin text-emerald-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span class="text-xs font-semibold">{{ __('Parsing files via microservice, separating columns, mapping categories & auto-resolving SIS enrollment...') }}</span>
                    </div>

                    <!-- Parse Error Notice -->
                    <div x-show="parseError" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800" x-text="parseError"></div>
                </div>

                <!-- Step 2: Interactive Preview & Field Mapping Table (Shown after parse) -->
                <div x-show="previewData" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="text-base font-bold text-gray-950 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">2</span>
                                {{ __('Parsed Data Preview & Campus Resolution') }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="font-bold text-gray-900" x-text="previewData ? previewData.total_rows : 0"></span> {{ __('records parsed.') }}
                                <template x-if="previewData && previewData.matched_count">
                                    <span class="text-emerald-700 font-semibold" x-text="' &bull; ' + previewData.matched_count + ' matched in SIS (campus & enrollment auto-populated)'"></span>
                                </template>
                                <template x-if="previewData && previewData.unmatched_count">
                                    <span class="text-amber-700 font-semibold" x-text="' &bull; ' + previewData.unmatched_count + ' require campus routing'"></span>
                                </template>
                            </p>
                        </div>

                        <!-- Status Badge Counters -->
                        <div class="flex items-center gap-2 flex-wrap" x-show="previewData">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                                <svg class="h-3 w-3 text-emerald-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                <span x-text="(previewData?.matched_count || 0) + ' Auto-Resolved'"></span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 border border-amber-200">
                                <svg class="h-3 w-3 text-amber-600" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                <span x-text="(previewData?.unmatched_count || 0) + ' Needs Campus'"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Category Summary Pills (Replaces broken headers box) -->
                    <div class="flex items-center gap-2.5 flex-wrap rounded-xl bg-gray-50 p-4 border border-gray-200">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 mr-1">{{ __('Detected Categories:') }}</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-950 border border-emerald-300 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                            <span>TES-UniFAST (<span x-text="previewData?.category_counts?.['TES-UniFAST'] ?? 0"></span>)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3.5 py-1 text-xs font-bold text-blue-950 border border-blue-300 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                            <span>TDP (<span x-text="previewData?.category_counts?.['TDP'] ?? 0"></span>)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-3.5 py-1 text-xs font-bold text-purple-950 border border-purple-300 shadow-xs">
                            <span class="h-2 w-2 rounded-full bg-purple-600"></span>
                            <span>CMSP (<span x-text="previewData?.category_counts?.['CMSP'] ?? 0"></span>)</span>
                        </span>
                        <template x-for="(cnt, prog) in (previewData?.category_counts || {})" :key="prog">
                            <template x-if="!['TES-UniFAST', 'TDP', 'CMSP'].includes(prog) && cnt > 0">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-3.5 py-1 text-xs font-bold text-gray-800 border border-gray-300 shadow-xs">
                                    <span class="h-2 w-2 rounded-full bg-gray-600"></span>
                                    <span x-text="prog + ' (' + cnt + ')'"></span>
                                </span>
                            </template>
                        </template>
                    </div>

                    <!-- Target Campus / Fallback Destination Toolbar -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 p-5 border-2 border-emerald-300 shadow-sm">
                        <div class="flex-1 max-w-xl">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-xs font-bold text-white">✓</span>
                                <x-input-label for="campus_id" :value="__('Target Campus / Fallback (if missing in file & DB)')" class="text-sm font-extrabold text-emerald-950" />
                            </div>
                            <p class="text-xs text-emerald-800 mt-1">
                                {{ __('Assign campus destination to ALL records at once (never 1 by 1!). Delivers verified records immediately to that Campus Coordinator.') }}
                            </p>
                            <div class="mt-3 flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                <select
                                    id="campus_id"
                                    name="campus_id"
                                    x-model="selectedCampusId"
                                    @change="applyCampusToAll(true)"
                                    class="block w-full rounded-xl border-emerald-300 bg-white py-2 px-3 text-xs font-semibold text-emerald-950 shadow-sm focus:border-emerald-700 focus:ring-emerald-700"
                                >
                                    <option value="">{{ __('Select Target Campus Destination...') }}</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                                    @endforeach
                                </select>
                                <button
                                    type="button"
                                    @click="applyCampusToAll(true)"
                                    title="{{ __('Set every single row in the batch to this campus destination') }}"
                                    class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl bg-emerald-800 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700"
                                >
                                    {{ __('Set ALL Records') }}
                                </button>
                                <button
                                    type="button"
                                    @click="applyCampusToAll(false)"
                                    title="{{ __('Only update records that do not yet have a campus') }}"
                                    class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-xl border border-emerald-400 bg-white px-3 py-2 text-xs font-bold text-emerald-900 shadow-xs transition hover:bg-emerald-50"
                                >
                                    {{ __('Apply to Unassigned Only') }}
                                </button>
                            </div>
                        </div>

                        <!-- Default metadata inputs for backward compatibility -->
                        <input type="hidden" name="apply_campus_to_all" :value="overrideAllFlag ? '1' : '0'">
                        <input type="hidden" name="agency_name" value="{{ old('agency_name', 'CHED-UniFAST') }}">
                        <input type="hidden" name="academic_year" value="{{ old('academic_year', 'AY ' . $currentYear . '-' . ($currentYear + 1)) }}">
                        <input type="hidden" name="semester" value="{{ old('semester', '1st Semester') }}">
                        <datalist id="agencies-list">
                            @foreach($agencies as $ag)
                                <option value="{{ $ag->agency_name ?? $ag->name }}">{{ $ag->agency_name ?? $ag->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Preview Table -->
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 font-bold uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">{{ __('Category / Program') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Full Student Name') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Student ID') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Campus Destination') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Program / Course') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Enrollment Status') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Remarks / Routing') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="r in (previewData ? previewData.preview_rows : [])" :key="r.row_number">
                                    <tr class="hover:bg-gray-50/75">
                                        <td class="px-4 py-2.5 font-mono text-gray-400" x-text="'#' + r.row_number"></td>
                                        <td class="px-4 py-2.5">
                                            <span
                                                :class="r.scholarship_program?.includes('TES') ? 'bg-emerald-100 text-emerald-950' : (r.scholarship_program?.includes('TDP') ? 'bg-blue-100 text-blue-950' : 'bg-purple-100 text-purple-950')"
                                                class="rounded px-2 py-0.5 font-bold text-[11px]"
                                                x-text="r.scholarship_program || '—'"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="font-semibold text-gray-900" x-text="r.raw_agency_name || r.student_name || '—'"></div>
                                            <div class="text-[10px] text-gray-400 font-mono" x-show="r.source_file" x-text="r.source_file"></div>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <template x-if="r.student_id">
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-mono font-bold text-emerald-800 ring-1 ring-inset ring-emerald-600/20" x-text="r.student_id"></span>
                                            </template>
                                            <template x-if="!r.student_id">
                                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 italic">{{ __('Not in SIS') }}</span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <template x-if="r.campus">
                                                <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-800 border border-blue-200" x-text="r.campus"></span>
                                            </template>
                                            <template x-if="!r.campus">
                                                <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 italic">
                                                    {{ __('Select campus above') }}
                                                </span>
                                            </template>
                                            <input type="hidden" :name="'row_campuses[' + r.row_number + ']'" :value="r.campus_id">
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-gray-700">
                                            <span x-text="r.program || '—'"></span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-xs">
                                            <template x-if="r.enrollment_status === 'ENROLLED'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                    {{ __('ENROLLED') }}
                                                </span>
                                            </template>
                                            <template x-if="r.enrollment_status === 'NO_COR_PRINTED'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                    {{ __('NO COR PRINTED') }}
                                                </span>
                                            </template>
                                            <template x-if="r.enrollment_status === 'UNENROLLED'">
                                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                                    {{ __('UNENROLLED') }}
                                                </span>
                                            </template>
                                            <template x-if="!r.enrollment_status">
                                                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 italic">
                                                    {{ __('Pending Routing') }}
                                                </span>
                                            </template>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-gray-500">
                                            <span x-text="r.remarks || (r.campus ? 'Auto-resolved via SIS' : 'Awaiting coordinator delivery')"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Step 3: Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            @click="previewData = null; files = []; fileNames = []; totalSizeStr = ''"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            {{ __('Choose Different Files') }}
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-800 px-6 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Confirm & Stage Records') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
