<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Administration') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('User Management') }}</h2>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200" aria-labelledby="create-user-title">
                <div class="border-b border-gray-200 pb-4">
                    <h3 id="create-user-title" class="text-base font-semibold text-gray-950">{{ __('Create User Account') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Enter the person or agency name, login credentials, and account role.') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6" x-data="{ showPassword: false }">
                    @csrf

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <x-input-label for="name" :value="__('Full Name or Agency Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block min-h-11 w-full" :value="old('name')" required autofocus autocomplete="name" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                                <option value="">{{ __('Select a role') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block min-h-11 w-full" :value="old('email')" required autocomplete="username" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="md:col-start-2">
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" x-bind:type="showPassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" required autocomplete="new-password" />
                            <label class="mt-3 inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" x-model="showPassword" class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-700">
                                <span>{{ __('Show passwords') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Create Account') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200" aria-labelledby="user-list-title">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 id="user-list-title" class="text-base font-semibold text-gray-950">{{ __('Existing Accounts') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ number_format($users->total()) }} {{ __('registered accounts') }}</p>
                        </div>

                        <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 sm:grid-cols-[minmax(14rem,1fr)_13rem_auto]">
                            <div>
                                <x-input-label for="search" :value="__('Search')" />
                                <x-text-input id="search" name="search" type="search" class="mt-1 min-h-11 w-full" :value="request('search')" placeholder="Name or email" />
                            </div>
                            <div>
                                <x-input-label for="role_filter" :value="__('Filter by Role')" />
                                <select id="role_filter" name="role" class="mt-1 min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                    <option value="">{{ __('All roles') }}</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="min-h-11 self-end rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ __('Apply') }}</button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Role') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-600">{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $account)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $account->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $account->email }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $account->role->label() }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-700/15">{{ str($account->status)->headline() }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $account->created_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No user accounts match the selected filters.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">{{ $users->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
