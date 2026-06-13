<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-100 font-sans antialiased">
        @php
            $authenticatedUser = Auth::user();
            $isAdministrator = $authenticatedUser?->hasRole(\App\Enums\UserRole::Administrator) ?? false;
        @endphp

        <div class="min-h-screen bg-gray-100">
            @unless ($isAdministrator)
                @include('layouts.navigation')
            @endunless

            <div class="flex">
                @if ($isAdministrator)
                    @include('layouts.sidebar')
                @endif

                <div class="min-w-0 flex-1">
                    @isset($header)
                        <header class="border-b border-gray-200 bg-white shadow-sm">
                            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main id="main-content">
                        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                            <x-status-alerts />
                        </div>
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
