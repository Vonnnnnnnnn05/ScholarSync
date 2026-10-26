@props(['active', 'badgeKey' => null, 'badgeValue' => null])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-emerald-600 text-start text-base font-semibold text-emerald-800 bg-emerald-50 focus:outline-none focus:text-emerald-900 focus:bg-emerald-100 focus:border-emerald-700 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
$hasStaticBadge = $badgeValue && (int)$badgeValue > 0;
$badgeDisplay = $hasStaticBadge ? ((int)$badgeValue > 99 ? '99+' : (string)$badgeValue) : '';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <div class="flex items-center justify-between">
        <span>{{ $slot }}</span>

        @if ($badgeKey)
            <span
                x-show="counts['{{ $badgeKey }}'] > 0"
                @if (! $hasStaticBadge) x-cloak style="display: none;" @endif
                x-text="counts['{{ $badgeKey }}'] > 99 ? '99+' : counts['{{ $badgeKey }}']"
                class="inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold leading-none text-white shadow-sm"
                title="{{ $hasStaticBadge ? $badgeValue . ' pending' : '' }}"
                :title="(counts['{{ $badgeKey }}'] || 0) + ' pending'"
            >{{ $badgeDisplay }}</span>
        @elseif ($hasStaticBadge)
            <span class="inline-flex min-h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold leading-none text-white shadow-sm" title="{{ $badgeValue }} pending">
                {{ $badgeDisplay }}
            </span>
        @endif
    </div>
</a>
