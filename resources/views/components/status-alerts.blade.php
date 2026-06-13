@if (session('status'))
    <div role="status" class="mb-6 rounded-md border border-emerald-700/20 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
        {{ session('status') }}
    </div>
@endif

@if (session('warning'))
    <div role="alert" class="mb-6 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-950">
        {{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
        {{ __('Please review the highlighted fields and try again.') }}
    </div>
@endif
