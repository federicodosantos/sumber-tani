<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">

<x-flash-message />

<div class="flex min-h-screen font-mont" x-data="{ sidebarOpen: false }">

    {{-- Sidebar Component --}}
    <x-partials.sidebar />

    {{-- Main content --}}
    <main class="flex-1 transition-all lg:ml-72 overflow-x-hidden">

        {{-- Mobile menu button --}}
        <button @click="sidebarOpen = true"
                class="lg:hidden fixed top-4 left-4 z-30 p-2 rounded-lg bg-white shadow-lg text-gray-600 hover:bg-gray-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Content --}}
        <div class="p-4 lg:p-6 pt-16 lg:pt-6">
            {{ $slot }}
        </div>

    </main>
</div>

@stack('scripts')
</body>

</html>