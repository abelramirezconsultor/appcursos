<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Bravon') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 45%, #f8fafc 45%, #f8fafc 100%);
            min-height: 100vh;
            color: #0f172a;
        }
        .container { max-width: 1080px; margin: 0 auto; padding: 24px; }
        .topbar { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid transparent;
            transition: .15s ease;
        }
        .btn-outline { color: #e2e8f0; border-color: rgba(226, 232, 240, .45); }
        .btn-outline:hover { background: rgba(255,255,255,.12); }
        .btn-solid { background: #111827; color: #fff; }
        .btn-solid:hover { background: #000; }
        .hero {
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(255,255,255,.5);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(2, 6, 23, .12);
            padding: 28px;
        }
        .brand { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .brand img {
            width: auto;
            max-width: 100%;
            height: 72px;
            object-fit: contain;
            object-position: left center;
            border-radius: 8px;
            background: #fff;
            display: block;
        }
        h1 { margin: 0 0 10px; font-size: clamp(28px, 4vw, 42px); line-height: 1.1; }
        p { margin: 0; color: #334155; }
        .actions { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #0f172a; color: #fff; }
        .btn-secondary:hover { background: #020617; }
        .btn-light { background: #eef2ff; color: #1e1b4b; }
        .btn-light:hover { background: #e0e7ff; }
        .grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
        .card h3 { margin: 0 0 6px; font-size: 15px; }
        .card p { font-size: 14px; }
        @media (min-width: 860px) {
            .grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
    <div class="container">
        @if (Route::has('login'))
            <nav class="topbar">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline">Panel central</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Login admin</a>
                    <a href="{{ route('student.access.login') }}" class="btn btn-outline">Login student</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-solid">Crear cuenta</a>
                    @endif
                @endauth
            </nav>
        @endif

        <main class="hero">
            <div class="brand">
                <img src="{{ asset('images/bravon-logo.png') }}?v={{ filemtime(public_path('images/bravon-logo.png')) }}" alt="Bravon">
            </div>

            <h1>Plataforma Bravon</h1>
            <p>Gestiona tus academias, estudiantes y cursos multitenant desde un solo lugar.</p>

            <div class="actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Ir al panel</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Registrar nueva cuenta</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Entrar como admin</a>
                    <a href="{{ route('student.access.login') }}" class="btn btn-light">Entrar como estudiante</a>
                @endauth
            </div>

            <section class="grid">
                <article class="card">
                    <h3>Multitenancy real</h3>
                    <p>Cada cliente opera en su propio tenant con datos aislados.</p>
                </article>
                <article class="card">
                    <h3>Gamificación activa</h3>
                    <p>XP, niveles, racha e insignias para motivar el avance.</p>
                </article>
                <article class="card">
                    <h3>Flujos listos</h3>
                    <p>Admin central, admin tenant y student con activación por código.</p>
                </article>
            </section>
        </main>
    </div>
</body>
</html>
