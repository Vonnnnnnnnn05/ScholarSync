@props(['active', 'badgeKey' => null, 'badgeValue' => null])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-emerald-600 text-sm font-semibold leading-5 text-emerald-950 focus:outline-none focus:border-emerald-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
$hasStaticBadge = $badgeValue && (int)$badgeValue > 0;
$badgeDisplay = $hasStaticBadge ? ((int)$badgeValue > 99 ? '99+' : (string)$badgeValue) : '';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <span>{{ $slot }}</span>

    @if ($badgeKey)
        <span
            x-show="counts['{{ $badgeKey }}'] > 0"
            @if (! $hasStaticBadge) x-cloak style="display: none;" @endif
            x-text="counts['{{ $badgeKey }}'] > 99 ? '99+' : counts['{{ $badgeKey }}']"
            class="ms-1.5 inline-flex min-h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white shadow-sm"
            title="{{ $hasStaticBadge ? $badgeValue . ' pending' : '' }}"
            :title="(counts['{{ $badgeKey }}'] || 0) + ' pending'"
        >{{ $badgeDisplay }}</span>
    @elseif ($hasStaticBadge)
        <span class="ms-1.5 inline-flex min-h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white shadow-sm" title="{{ $badgeValue }} pending">
            {{ $badgeDisplay }}
        </span>
    @endif
</a>
