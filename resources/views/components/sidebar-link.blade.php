@props(['active' => false])

@php
$classes = $active
    ? 'flex min-h-10 items-center rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm'
    : 'flex min-h-10 items-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-950 focus:outline-none focus:ring-2 focus:ring-emerald-700';
@endphp

<a {{ $attributes->merge(['class' => $classes])->merge($active ? ['data-active-sidebar-link' => 'true'] : []) }}>
    {{ $slot }}
</a>
