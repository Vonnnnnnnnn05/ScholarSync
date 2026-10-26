@props(['active' => false, 'badgeKey' => null, 'badgeValue' => null])

@php
$classes = $active
    ? 'flex min-h-10 items-center justify-between rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm'
    : 'flex min-h-10 items-center justify-between rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-950 focus:outline-none focus:ring-2 focus:ring-emerald-700';
$hasStaticBadge = $badgeValue && (int)$badgeValue > 0;
$badgeDisplay = $hasStaticBadge ? ((int)$badgeValue > 99 ? '99+' : (string)$badgeValue) : '';
@endphp

<a {{ $attributes->merge(['class' => $classes])->merge($active ? ['data-active-sidebar-link' => 'true'] : []) }}>
    <span>{{ $slot }}</span>

    @if ($badgeKey)
        <span
            x-show="counts['{{ $badgeKey }}'] > 0"
            @if (! $hasStaticBadge) x-cloak style="display: none;" @endif
            x-text="counts['{{ $badgeKey }}'] > 99 ? '99+' : counts['{{ $badgeKey }}']"
            class="ms-2 inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold leading-none text-white shadow-sm"
            title="{{ $hasStaticBadge ? $badgeValue . ' pending' : '' }}"
            :title="(counts['{{ $badgeKey }}'] || 0) + ' pending'"
        >{{ $badgeDisplay }}</span>
    @elseif ($hasStaticBadge)
        <span class="ms-2 inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold leading-none text-white shadow-sm" title="{{ $badgeValue }} pending">
            {{ $badgeDisplay }}
        </span>
    @endif
</a>
