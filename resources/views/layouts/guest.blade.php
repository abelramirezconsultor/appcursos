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

        <style>
            body.master-guest {
                margin: 0;
                background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 45%, #f8fafc 45%, #f8fafc 100%);
                min-height: 100vh;
            }
            .guest-container {
                max-width: 1080px;
                margin: 0 auto;
                padding: 24px;
            }
            .guest-topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                margin-bottom: 24px;
                flex-wrap: wrap;
            }
            .guest-logo {
                width: auto;
                max-width: 100%;
                height: 72px;
                object-fit: contain;
                object-position: left center;
                border-radius: 8px;
                background: #fff;
                display: block;
            }
            .guest-nav {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            .guest-btn {
                display: inline-block;
                padding: 10px 14px;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                border: 1px solid transparent;
                transition: .15s ease;
                font-size: 14px;
                line-height: 1.2;
            }
            .guest-btn-outline {
                color: #e2e8f0;
                border-color: rgba(226, 232, 240, .45);
            }
            .guest-btn-outline:hover {
                background: rgba(255,255,255,.12);
            }
            .guest-btn-solid {
                background: #111827;
                color: #fff;
            }
            .guest-btn-solid:hover {
                background: #000;
            }
            .guest-card {
                width: 100%;
                max-width: 520px;
                margin: 0 auto;
                background: rgba(255,255,255,.97);
                border: 1px solid rgba(255,255,255,.6);
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(2, 6, 23, .12);
                overflow: hidden;
                padding: 20px 24px;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased master-guest">
        <div class="guest-container">
            <div class="guest-topbar">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/bravon-logo.png') }}?v={{ filemtime(public_path('images/bravon-logo.png')) }}" alt="Bravon" class="guest-logo" />
                </a>

                @if (Route::has('login'))
                    <nav class="guest-nav">
                        <a href="{{ url('/') }}" class="guest-btn guest-btn-outline">Inicio</a>
                        @guest
                            <a href="{{ route('login') }}" class="guest-btn guest-btn-outline">Login admin</a>
                            <a href="{{ route('student.access.login') }}" class="guest-btn guest-btn-outline">Login student</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="guest-btn guest-btn-solid">Crear cuenta</a>
                            @endif
                        @else
                            <a href="{{ route('dashboard') }}" class="guest-btn guest-btn-solid">Panel central</a>
                        @endguest
                    </nav>
                @endif
            </div>

            <div class="guest-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
