@php
    $user = Auth::user();
    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard*', 'show' => true],
        ['label' => 'Certificates', 'route' => 'student.certificate-requests.index', 'active' => 'student.certificate-requests.*', 'show' => $user->hasRole(\App\Enums\UserRole::Student)],
        ['label' => 'Renewals', 'route' => 'student.scholarship-renewals.index', 'active' => 'student.scholarship-renewals.*', 'show' => $user->hasRole(\App\Enums\UserRole::Student)],
        ['label' => 'OR Verification', 'route' => 'admin.official-receipts.index', 'active' => 'admin.official-receipts.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Certificates', 'route' => 'admin.certificates.index', 'active' => 'admin.certificates.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Evaluations', 'route' => 'evaluator.scholarship-renewals.index', 'active' => 'evaluator.scholarship-renewals.*', 'show' => $user->hasAnyRole([\App\Enums\UserRole::Administrator, \App\Enums\UserRole::Coordinator])],
        ['label' => 'Monitoring', 'route' => 'admin.monitoring.dashboard', 'active' => 'admin.monitoring.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Masterlists', 'route' => 'agency.masterlists.index', 'active' => 'agency.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::ScholarshipAgency)],
        ['label' => 'Validation', 'route' => 'coordinator.masterlists.index', 'active' => 'coordinator.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::Coordinator)],
        ['label' => 'Approvals', 'route' => 'chairman.masterlists.index', 'active' => 'chairman.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::ScholarshipChairman)],
    ];
@endphp

<aside id="admin-sidebar" class="hidden w-72 shrink-0 border-r border-emerald-900/10 bg-white lg:block">
    <div class="sticky top-0 flex min-h-screen flex-col">
        <div class="border-b border-emerald-900/10 px-5 py-5">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-12 w-12 object-contain" />
                <div>
                    <p class="text-sm font-bold uppercase text-emerald-900">{{ __('ScholarSync') }}</p>
                    <p class="text-xs font-medium text-gray-600">{{ $user->role->label() }}</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4">
            @foreach ($links as $link)
                @if ($link['show'])
                    <x-sidebar-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                        {{ $link['label'] }}
                    </x-sidebar-link>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-emerald-900/10 px-5 py-4">
            <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
            <p class="mt-1 truncate text-xs text-gray-600">{{ $user->email }}</p>

            <div class="mt-4 grid gap-2">
                <a
                    href="{{ route('profile.edit') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-md border border-emerald-900/15 px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700"
                >
                    {{ __('Profile') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-emerald-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                    >
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
