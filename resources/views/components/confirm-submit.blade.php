@props(['message' => 'Are you sure you want to continue?'])

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-10 items-center justify-center rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2']) }}
    onclick="return window.confirm(@js($message))">
    {{ $slot }}
</button>
