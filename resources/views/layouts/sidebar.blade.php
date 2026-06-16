@php
    $user = Auth::user();
    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard*', 'show' => true],
        ['label' => 'Certificates', 'route' => 'student.certificate-requests.index', 'active' => 'student.certificate-requests.*', 'show' => $user->hasRole(\App\Enums\UserRole::Student)],
        ['label' => 'Renewals', 'route' => 'student.scholarship-renewals.index', 'active' => 'student.scholarship-renewals.*', 'show' => $user->hasRole(\App\Enums\UserRole::Student)],
        ['label' => 'OR Verification', 'route' => 'admin.official-receipts.index', 'active' => 'admin.official-receipts.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Certificates', 'route' => 'admin.certificates.index', 'active' => 'admin.certificates.*', 'show' => $user->hasRole(\App\Enums\UserRole::Administrator)],
        ['label' => 'Evaluations', 'route' => 'evaluator.scholarship-renewals.index', 'active' => 'evaluator.scholarship-renewals.*', 'show' => $user->hasAnyRole([\App\Enums\UserRole::Administrator, \App\Enums\UserRole::Coordinator])],
        ['label' => 'Masterlists', 'route' => 'agency.masterlists.index', 'active' => 'agency.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::ScholarshipAgency)],
        ['label' => 'Validation', 'route' => 'coordinator.masterlists.index', 'active' => 'coordinator.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::Coordinator)],
        ['label' => 'Approvals', 'route' => 'chairman.masterlists.index', 'active' => 'chairman.masterlists.*', 'show' => $user->hasRole(\App\Enums\UserRole::ScholarshipChairman)],
        ['label' => 'Profile', 'route' => 'profile.edit', 'active' => 'profile.*', 'show' => true],
    ];
    $monitoringLinks = [
        ['label' => 'Student Profiles', 'route' => 'admin.monitoring.students.index', 'active' => 'admin.monitoring.students.*'],
        ['label' => 'Scholar Records', 'route' => 'admin.monitoring.scholars.index', 'active' => 'admin.monitoring.scholars.*'],
        ['label' => 'Transactions', 'route' => 'admin.monitoring.transactions.index', 'active' => 'admin.monitoring.transactions.*'],
        ['label' => 'Fund Sources', 'route' => 'admin.monitoring.programs.index', 'active' => 'admin.monitoring.programs.*'],
        ['label' => 'Audit Trail', 'route' => 'admin.monitoring.audit.index', 'active' => 'admin.monitoring.audit.*'],
    ];
    $reportLinks = [
        ['label' => 'Reports Home', 'route' => 'admin.reports.index', 'type' => null],
        ['label' => 'Scholar Information', 'route' => 'admin.reports.preview', 'type' => 'scholar_information'],
        ['label' => 'Certificate Requests', 'route' => 'admin.reports.preview', 'type' => 'certificate_requests'],
        ['label' => 'OR Verification', 'route' => 'admin.reports.preview', 'type' => 'or_verification'],
        ['label' => 'Masterlists', 'route' => 'admin.reports.preview', 'type' => 'masterlists'],
        ['label' => 'Renewal Evaluations', 'route' => 'admin.reports.preview', 'type' => 'renewal_evaluations'],
        ['label' => 'Requirement Submissions', 'route' => 'admin.reports.preview', 'type' => 'requirement_submissions'],
        ['label' => 'Fund Sources', 'route' => 'admin.reports.preview', 'type' => 'fund_sources'],
        ['label' => 'Approved and Rejected', 'route' => 'admin.reports.preview', 'type' => 'approved_rejected'],
    ];
@endphp

<aside id="role-sidebar" class="w-full shrink-0 border-b border-emerald-900/10 bg-white lg:w-72 lg:border-b-0 lg:border-r">
    <div class="flex flex-col lg:sticky lg:top-0 lg:h-screen lg:min-h-screen">
        <div class="border-b border-emerald-900/10 px-5 py-5">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-12 w-12 object-contain" />
                <div>
                    <p class="text-sm font-bold uppercase text-emerald-900">{{ __('ScholarSync') }}</p>
                    <p class="text-xs font-medium text-gray-600">{{ $user->role->label() }}</p>
                </div>
            </div>
        </div>

        <nav class="grid gap-1 scroll-smooth px-3 py-4 sm:grid-cols-2 lg:flex lg:flex-1 lg:flex-col lg:space-y-1 lg:overflow-y-auto">
            @foreach ($links as $link)
                @if ($link['show'])
                    <x-sidebar-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                        {{ $link['label'] }}
                    </x-sidebar-link>

                    @if ($link['label'] === 'Evaluations' && $user->hasRole(\App\Enums\UserRole::Administrator))
                        <details class="group" @if (request()->routeIs('admin.monitoring.*')) open @endif>
                            <summary class="{{ request()->routeIs('admin.monitoring.*') ? 'flex min-h-11 cursor-pointer list-none items-center justify-between rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm' : 'flex min-h-11 cursor-pointer list-none items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700' }}">
                                <span>{{ __('Monitoring') }}</span>
                                <svg class="h-4 w-4 transition group-open:rotate-90" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                </svg>
                            </summary>

                            <div class="mt-1 grid gap-1 border-l-2 border-emerald-100 pl-3 lg:ml-3">
                                @foreach ($monitoringLinks as $monitoringLink)
                                    <a
                                        href="{{ route($monitoringLink['route']) }}"
                                        @if (request()->routeIs($monitoringLink['active'])) data-active-sidebar-link="true" @endif
                                        class="{{ request()->routeIs($monitoringLink['active']) ? 'flex min-h-10 items-center rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 ring-1 ring-emerald-700/10' : 'flex min-h-10 items-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700' }}"
                                    >
                                        {{ __($monitoringLink['label']) }}
                                    </a>
                                @endforeach
                            </div>
                        </details>

                        <details class="group" @if (request()->routeIs('admin.reports.*')) open @endif>
                            <summary class="{{ request()->routeIs('admin.reports.*') ? 'flex min-h-11 cursor-pointer list-none items-center justify-between rounded-md bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm' : 'flex min-h-11 cursor-pointer list-none items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700' }}">
                                <span>{{ __('Reports') }}</span>
                                <svg class="h-4 w-4 transition group-open:rotate-90" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                                </svg>
                            </summary>

                            <div class="mt-1 grid gap-1 border-l-2 border-emerald-100 pl-3 lg:ml-3">
                                @foreach ($reportLinks as $reportLink)
                                    @php
                                        $href = $reportLink['type']
                                            ? route($reportLink['route'], ['type' => $reportLink['type'], 'format' => 'pdf'])
                                            : route($reportLink['route']);
                                        $isActive = $reportLink['type']
                                            ? request()->routeIs('admin.reports.preview') && request('type') === $reportLink['type']
                                            : request()->routeIs('admin.reports.index');
                                    @endphp

                                    <a
                                        href="{{ $href }}"
                                        @if ($isActive) data-active-sidebar-link="true" @endif
                                        class="{{ $isActive ? 'flex min-h-10 items-center rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 ring-1 ring-emerald-700/10' : 'flex min-h-10 items-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700' }}"
                                    >
                                        {{ __($reportLink['label']) }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endif
            @endforeach
        </nav>

        <div class="mt-auto border-t border-emerald-900/10 px-5 py-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-red-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                >
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</aside>
