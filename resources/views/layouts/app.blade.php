<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bravon') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $isGamingShell = request()->routeIs('dashboard')
            || request()->routeIs('tenant-admin.show')
            || request()->routeIs('tenants.create')
            || request()->routeIs('profile.*')
            || request()->is('dashboard')
            || request()->is('tenant-admin/*')
            || request()->is('tenants/create')
            || request()->is('profile');

        $isSidebarLayout = auth()->check() && $isGamingShell;
    @endphp
    <body x-data="{ sidebarExpanded: false }" class="font-sans antialiased {{ $isGamingShell ? 'gaming-shell' : '' }}">
        <div class="min-h-screen {{ $isGamingShell ? 'bg-transparent' : 'bg-gray-100' }}">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="{{ $isGamingShell ? 'bg-transparent border-b border-orange-500/30' : 'bg-white shadow' }} {{ $isSidebarLayout ? '' : '' }}" @if($isSidebarLayout) :class="sidebarExpanded ? 'sm:pl-64' : 'sm:pl-20'" @endif>
                    <div class="{{ $isSidebarLayout ? 'w-full py-6 px-4 sm:px-6 lg:px-8' : 'max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8' }}">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="{{ $isGamingShell ? 'gaming-main-pane' : '' }}" @if($isSidebarLayout) :class="sidebarExpanded ? 'sm:pl-64' : 'sm:pl-20'" @endif>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
