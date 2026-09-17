@props(['active' => false])

@php
$classes = $active
    ? 'flex min-h-9 items-center rounded-md bg-emerald-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm'
    : 'flex min-h-9 items-center rounded-md px-3 py-1.5 text-xs font-medium text-emerald-950 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700';
@endphp

<a {{ $attributes->merge(['class' => $classes])->merge($active ? ['data-active-sidebar-link' => 'true'] : []) }}>
    {{ $slot }}
</a>
