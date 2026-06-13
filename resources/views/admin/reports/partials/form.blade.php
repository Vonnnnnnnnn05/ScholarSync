<div>
    <x-input-label for="type" :value="__('Report Type')" />
    <select id="type" name="type" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(old('type', request('type')) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('type')" class="mt-2" />
</div>

<div>
    <x-input-label for="format" :value="__('Default Export Format')" />
    <select id="format" name="format" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
        @foreach ($formats as $value => $label)
            <option value="{{ $value }}" @selected(old('format', request('format', 'pdf')) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('format')" class="mt-2" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="date_from" :value="__('Date From')" />
        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="old('date_from', request('date_from'))" />
    </div>
    <div>
        <x-input-label for="date_to" :value="__('Date To')" />
        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="old('date_to', request('date_to'))" />
    </div>
</div>

<div>
    <x-input-label for="status" :value="__('Status')" />
    <x-text-input id="status" name="status" class="mt-1 block w-full" :value="old('status', request('status'))" placeholder="approved, rejected, submitted..." />
</div>

<div>
    <x-input-label for="student" :value="__('Student')" />
    <x-text-input id="student" name="student" class="mt-1 block w-full" :value="old('student', request('student'))" placeholder="Student ID or name" />
</div>

<div>
    <x-input-label for="fund_source" :value="__('Fund Source')" />
    <x-text-input id="fund_source" name="fund_source" class="mt-1 block w-full" :value="old('fund_source', request('fund_source'))" placeholder="CHED, LGU, agency fund..." />
</div>
