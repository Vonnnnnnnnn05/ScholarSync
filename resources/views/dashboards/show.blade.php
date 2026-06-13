<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">{{ __('ScholarSync') }}</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    {{ __($title) }}
                </h2>
            </div>
            <span class="inline-flex w-fit rounded-md bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800 ring-1 ring-emerald-700/15">
                {{ __($role->label()) }}
            </span>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-l-4 border-emerald-700 p-6 sm:p-8">
                    <p class="text-sm font-semibold uppercase text-emerald-700">
                        {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}
                    </p>
                    <h3 class="mt-3 max-w-3xl text-2xl font-semibold text-gray-950">
                        {{ __($summary) }}
                    </h3>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
                        {{ __('This dashboard is tailored to your account role. More workflow tools can be added here as the next phases are completed.') }}
                    </p>
                </div>
            </section>

            @if ($role === \App\Enums\UserRole::Administrator && $adminDashboard)
                @php
                    $metricAccentClasses = [
                        'emerald' => 'bg-emerald-50 text-emerald-800 ring-emerald-700/15',
                        'blue' => 'bg-blue-50 text-blue-800 ring-blue-700/15',
                        'amber' => 'bg-amber-50 text-amber-800 ring-amber-700/15',
                        'slate' => 'bg-slate-50 text-slate-800 ring-slate-700/15',
                    ];
                    $certificateColors = ['bg-amber-500', 'bg-blue-600', 'bg-rose-600', 'bg-emerald-700'];
                    $renewalColors = ['bg-blue-500', 'bg-amber-500', 'bg-emerald-700', 'bg-rose-600', 'bg-slate-600'];
                    $verificationColors = ['#047857', '#1d4ed8', '#d97706', '#dc2626', '#64748b'];
                    $verificationTotal = collect($adminDashboard['verificationStatuses'])->sum('value');
                    $currentAngle = 0;
                    $verificationGradient = collect($adminDashboard['verificationStatuses'])->map(function ($item, $index) use (&$currentAngle, $verificationTotal, $verificationColors) {
                        if ($verificationTotal === 0) {
                            return null;
                        }

                        $start = $currentAngle;
                        $currentAngle += ($item['value'] / $verificationTotal) * 360;

                        return $verificationColors[$index].' '.$start.'deg '.$currentAngle.'deg';
                    })->filter()->implode(', ') ?: '#e5e7eb 0deg 360deg';
                    $certificateMax = max(1, collect($adminDashboard['certificateStatuses'])->max('value'));
                    $renewalMax = max(1, collect($adminDashboard['renewalStatuses'])->max('value'));
                    $roleMax = max(1, collect($adminDashboard['roleDistribution'])->max('value'));
                    $trendValues = collect($adminDashboard['monthlyCertificateRequests']);
                    $trendMax = max(1, $trendValues->max('value'));
                    $trendWidth = 540;
                    $trendHeight = 180;
                    $trendStep = $trendValues->count() > 1 ? $trendWidth / ($trendValues->count() - 1) : $trendWidth;
                    $trendPoints = $trendValues->values()->map(function ($item, $index) use ($trendStep, $trendMax, $trendHeight) {
                        $x = round($index * $trendStep, 2);
                        $y = round(($trendHeight - 20) - (($item['value'] / $trendMax) * 120), 2);

                        return $x.','.$y;
                    })->implode(' ');
                @endphp

                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($adminDashboard['metrics'] as $metric)
                        <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                            <p class="text-sm font-medium text-gray-600">{{ __($metric['label']) }}</p>
                            <div class="mt-3 flex items-end justify-between gap-4">
                                <p class="text-3xl font-semibold text-gray-950">{{ number_format($metric['value']) }}</p>
                                <span class="rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $metricAccentClasses[$metric['accent']] }}">
                                    {{ __('Live') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 xl:col-span-2">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-700">{{ __('Operations') }}</p>
                                <h3 class="text-base font-semibold text-gray-950">{{ __('Certificate Request Trend') }}</h3>
                            </div>
                            <p class="text-sm text-gray-600">{{ __('Last 6 months') }}</p>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-md border border-gray-200 bg-gray-50 p-4">
                            <svg viewBox="0 0 540 180" role="img" aria-label="{{ __('Certificate request trend over the last six months') }}" class="h-64 w-full">
                                <defs>
                                    <linearGradient id="certificateTrendFill" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#047857" stop-opacity="0.22" />
                                        <stop offset="100%" stop-color="#047857" stop-opacity="0.02" />
                                    </linearGradient>
                                </defs>
                                @foreach ([40, 80, 120, 160] as $lineY)
                                    <line x1="0" x2="540" y1="{{ $lineY }}" y2="{{ $lineY }}" stroke="#e5e7eb" stroke-width="1" />
                                @endforeach
                                <polygon points="0,160 {{ $trendPoints }} 540,160" fill="url(#certificateTrendFill)" />
                                <polyline points="{{ $trendPoints }}" fill="none" stroke="#047857" stroke-linecap="round" stroke-linejoin="round" stroke-width="4" />
                                @foreach ($trendValues->values() as $point)
                                    @php
                                        $x = round($loop->index * $trendStep, 2);
                                        $y = round(($trendHeight - 20) - (($point['value'] / $trendMax) * 120), 2);
                                    @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#facc15" stroke="#047857" stroke-width="3" />
                                    <text x="{{ $x }}" y="176" text-anchor="middle" class="fill-gray-600 text-xs font-semibold">{{ $point['label'] }}</text>
                                @endforeach
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-emerald-700">{{ __('Masterlists') }}</p>
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Verification Mix') }}</h3>
                        <div class="mt-6 flex flex-col items-center gap-5">
                            <div class="relative h-44 w-44 rounded-full" style="background: conic-gradient({{ $verificationGradient }});">
                                <div class="absolute inset-8 flex flex-col items-center justify-center rounded-full bg-white text-center ring-1 ring-gray-200">
                                    <span class="text-3xl font-semibold text-gray-950">{{ number_format($verificationTotal) }}</span>
                                    <span class="text-xs font-semibold uppercase text-gray-500">{{ __('Records') }}</span>
                                </div>
                            </div>
                            <div class="w-full space-y-3">
                                @foreach ($adminDashboard['verificationStatuses'] as $status)
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="flex items-center gap-2 font-medium text-gray-700">
                                            <span class="h-3 w-3 rounded-sm" style="background-color: {{ $verificationColors[$loop->index] }}"></span>
                                            {{ __($status['label']) }}
                                        </span>
                                        <span class="font-semibold text-gray-950">{{ number_format($status['value']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-emerald-700">{{ __('Certificates') }}</p>
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Request Status') }}</h3>
                        <div class="mt-6 space-y-4">
                            @foreach ($adminDashboard['certificateStatuses'] as $status)
                                @php($width = max(6, ($status['value'] / $certificateMax) * 100))
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                        <span class="font-medium text-gray-700">{{ __($status['label']) }}</span>
                                        <span class="font-semibold text-gray-950">{{ number_format($status['value']) }}</span>
                                    </div>
                                    <div class="h-3 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full {{ $certificateColors[$loop->index] }}" style="width: {{ $width }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <p class="text-sm font-medium text-emerald-700">{{ __('Renewals') }}</p>
                        <h3 class="text-base font-semibold text-gray-950">{{ __('Evaluation Status') }}</h3>
                        <div class="mt-6 flex h-64 items-end gap-3 border-b border-l border-gray-200 px-3 pb-3">
                            @foreach ($adminDashboard['renewalStatuses'] as $status)
                                @php($height = max(8, ($status['value'] / $renewalMax) * 100))
                                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                    <span class="text-xs font-semibold text-gray-700">{{ number_format($status['value']) }}</span>
                                    <div class="w-full max-w-12 rounded-t-md {{ $renewalColors[$loop->index] }}" style="height: {{ $height }}%"></div>
                                    <span class="min-h-10 text-center text-xs font-medium leading-tight text-gray-600">{{ __($status['label']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <p class="text-sm font-medium text-emerald-700">{{ __('Access Control') }}</p>
                    <h3 class="text-base font-semibold text-gray-950">{{ __('User Role Distribution') }}</h3>
                    <div class="mt-6 grid gap-4 md:grid-cols-5">
                        @foreach ($adminDashboard['roleDistribution'] as $roleItem)
                            @php($height = max(10, ($roleItem['value'] / $roleMax) * 100))
                            <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                <div class="flex h-28 items-end rounded bg-white px-3 pb-3 ring-1 ring-gray-100">
                                    <div class="w-full rounded-t bg-emerald-700" style="height: {{ $height }}%"></div>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-gray-950">{{ number_format($roleItem['value']) }}</p>
                                <p class="mt-1 text-xs font-medium text-gray-600">{{ __($roleItem['label']) }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($items as $item)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <div class="flex h-10 w-10 items-center justify-center rounded-md bg-yellow-100 text-sm font-bold text-emerald-900 ring-1 ring-yellow-300">
                            {{ $loop->iteration }}
                        </div>
                        <p class="mt-4 text-sm font-semibold text-gray-950">{{ __($item) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
