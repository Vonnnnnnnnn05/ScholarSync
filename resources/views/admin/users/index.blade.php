<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-emerald-700">{{ __('Administration') }}</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('User Management') }}</h2>
        </div>
    </x-slot>

    @php($createModalOpen = $errors->any() && old('status') === null)
    <div class="py-10" x-data="{ createOpen: @js($createModalOpen), showCreatePassword: false }" data-create-modal-open="{{ $createModalOpen ? 'true' : 'false' }}">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div x-cloak x-show="createOpen" x-on:keydown.escape.window="createOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
                <div class="absolute inset-0 bg-gray-950/50" x-on:click="createOpen = false" aria-hidden="true"></div>
                <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 pb-4">
                        <h3 id="create-user-title" class="text-lg font-semibold text-gray-950">{{ __('Create User Account') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Enter login credentials, role, and campus assignment when required.') }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-5">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block min-h-11 w-full" :value="old('name')" required autocomplete="name" />
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
                                <x-input-label for="campus_id" :value="__('Assigned Campus (Coordinator and Registrar)')" />
                                <select id="campus_id" name="campus_id" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                    <option value="">{{ __('University-wide / Not applicable') }}</option>
                                    @foreach ($campuses as $campus)
                                        <option value="{{ $campus->id }}" @selected((string) old('campus_id') === (string) $campus->id)>{{ $campus->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('campus_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" x-bind:type="showCreatePassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" x-bind:type="showCreatePassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" required autocomplete="new-password" />
                                <label class="mt-2 inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium text-gray-700">
                                    <input type="checkbox" x-model="showCreatePassword" class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-700">
                                    <span>{{ __('Show passwords') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" x-on:click="createOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">{{ __('Cancel') }}</button>
                            <x-primary-button>{{ __('Create Account') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200" aria-labelledby="user-list-title">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 id="user-list-title" class="text-base font-semibold text-gray-950">{{ __('Existing Accounts') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ number_format($users->total()) }} {{ __('registered accounts') }}</p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="button" data-open-create-account x-on:click="createOpen = true" class="inline-flex min-h-11 self-end items-center justify-center gap-2 rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>{{ __('Create Account') }}</span>
                            </button>
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
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-600">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $account)
                                <tr x-data="{ editOpen: false, deleteOpen: false, showPassword: false }">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-950">{{ $account->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $account->email }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $account->role->label() }}</td>
                                    <td class="px-6 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                            'bg-emerald-50 text-emerald-800 ring-emerald-700/15' => $account->status === 'active',
                                            'bg-gray-100 text-gray-700 ring-gray-500/20' => $account->status !== 'active',
                                        ])>{{ str($account->status)->headline() }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $account->created_at->format('M j, Y') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" x-on:click="editOpen = true" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-700 text-xs text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2" aria-label="{{ __('Edit :name', ['name' => $account->name]) }}" title="{{ __('Edit account') }}">
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                            </button>

                                            @if (auth()->id() !== $account->id)
                                                <button type="button" x-on:click="deleteOpen = true" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-red-300 text-xs text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2" aria-label="{{ __('Delete :name', ['name' => $account->name]) }}" title="{{ __('Delete account') }}">
                                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                </button>
                                            @else
                                                <span class="px-2 text-xs font-medium text-gray-500">{{ __('Current account') }}</span>
                                            @endif
                                        </div>

                                        <div x-cloak x-show="editOpen" x-on:keydown.escape.window="editOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="edit-account-{{ $account->id }}-title">
                                            <div class="absolute inset-0 bg-gray-950/50" x-on:click="editOpen = false" aria-hidden="true"></div>
                                            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                                                <div class="border-b border-gray-200 pb-4">
                                                    <h3 id="edit-account-{{ $account->id }}-title" class="text-lg font-semibold text-gray-950">{{ __('Edit Account') }}</h3>
                                                    <p class="mt-1 text-sm text-gray-600">{{ __('Update account details, access, or password.') }}</p>
                                                </div>

                                                <form method="POST" action="{{ route('admin.users.update', $account) }}" class="mt-5">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="grid gap-5 sm:grid-cols-2">
                                                        <div>
                                                            <x-input-label for="edit_name_{{ $account->id }}" :value="__('Full Name')" />
                                                            <x-text-input id="edit_name_{{ $account->id }}" name="name" type="text" class="mt-1 block min-h-11 w-full" :value="$account->name" required />
                                                        </div>
                                                        <div>
                                                            <x-input-label for="edit_email_{{ $account->id }}" :value="__('Email Address')" />
                                                            <x-text-input id="edit_email_{{ $account->id }}" name="email" type="email" class="mt-1 block min-h-11 w-full" :value="$account->email" required />
                                                        </div>
                                                        <div>
                                                            <x-input-label for="edit_role_{{ $account->id }}" :value="__('Role')" />
                                                            <select id="edit_role_{{ $account->id }}" name="role" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <x-input-label for="edit_campus_{{ $account->id }}" :value="__('Assigned Campus')" />
                                                            <select id="edit_campus_{{ $account->id }}" name="campus_id" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700">
                                                                <option value="">{{ __('University-wide / Not applicable') }}</option>
                                                                @foreach ($campuses as $campus)
                                                                    <option value="{{ $campus->id }}" @selected($account->campus_id === $campus->id)>{{ $campus->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <x-input-label for="edit_status_{{ $account->id }}" :value="__('Status')" />
                                                            <select id="edit_status_{{ $account->id }}" name="status" class="mt-1 block min-h-11 w-full rounded-md border-emerald-900/20 bg-white text-sm text-gray-900 shadow-sm focus:border-emerald-700 focus:ring-emerald-700" required>
                                                                <option value="active" @selected($account->status === 'active')>{{ __('Active') }}</option>
                                                                <option value="inactive" @selected($account->status === 'inactive')>{{ __('Inactive') }}</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <x-input-label for="edit_password_{{ $account->id }}" :value="__('New Password (optional)')" />
                                                            <x-text-input id="edit_password_{{ $account->id }}" name="password" x-bind:type="showPassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" autocomplete="new-password" />
                                                        </div>
                                                        <div class="sm:col-start-2">
                                                            <x-input-label for="edit_password_confirmation_{{ $account->id }}" :value="__('Confirm New Password')" />
                                                            <x-text-input id="edit_password_confirmation_{{ $account->id }}" name="password_confirmation" x-bind:type="showPassword ? 'text' : 'password'" class="mt-1 block min-h-11 w-full" autocomplete="new-password" />
                                                            <label class="mt-2 inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-gray-700">
                                                                <input type="checkbox" x-model="showPassword" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                                                                <span>{{ __('Show passwords') }}</span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                        <button type="button" x-on:click="editOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">{{ __('Cancel') }}</button>
                                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">{{ __('Save Changes') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        @if (auth()->id() !== $account->id)
                                            <div x-cloak x-show="deleteOpen" x-on:keydown.escape.window="deleteOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-account-{{ $account->id }}-title">
                                                <div class="absolute inset-0 bg-gray-950/50" x-on:click="deleteOpen = false" aria-hidden="true"></div>
                                                <div class="relative w-full max-w-md rounded-lg bg-white p-6 text-left shadow-xl ring-1 ring-gray-200">
                                                    <h3 id="delete-account-{{ $account->id }}-title" class="text-lg font-semibold text-gray-950">{{ __('Delete Account Access?') }}</h3>
                                                    <p class="mt-2 text-sm leading-6 text-gray-600">
                                                        {{ __('This will deactivate :name. Their records will be preserved, but they will no longer be able to log in.', ['name' => $account->name]) }}
                                                    </p>
                                                    <form method="POST" action="{{ route('admin.users.destroy', $account) }}" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" x-on:click="deleteOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">{{ __('Cancel') }}</button>
                                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2">{{ __('Delete') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-600">{{ __('No user accounts match the selected filters.') }}</td>
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
