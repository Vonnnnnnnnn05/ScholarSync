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
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-8 bg-[radial-gradient(circle_at_top,#fff7bf_0,#f7fbef_34%,#eef7f1_58%,#e2efe5_100%)]">
            <div class="text-center">
                <a href="/" class="inline-flex items-center justify-center rounded-full drop-shadow-xl transition hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-emerald-700/25">
                    <x-application-logo class="h-24 w-24 sm:h-28 sm:w-28" />
                </a>
                <p class="mt-4 text-sm font-semibold uppercase text-emerald-900">Sultan Kudarat State University</p>
                <p class="mt-1 text-xs font-medium text-emerald-800/75">ScholarSync Portal</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 border-t-4 border-emerald-700 bg-white/95 px-6 py-5 shadow-xl shadow-emerald-950/10 ring-1 ring-emerald-900/10 sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
