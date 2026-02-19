<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar curso</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.game-bg {
            background: linear-gradient(135deg, #111827 0%, #1d4ed8 50%, #22d3ee 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body class="game-bg">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark border-bottom border-secondary">
        <div class="container">
            <span class="navbar-brand fw-semibold">🎮 {{ tenant('name') ?? 'Plataforma' }}</span>
            <div class="d-flex gap-3 align-items-center">
                <a class="nav-link text-white" href="{{ route('tenant.student.login', ['tenant' => $tenantRouteKey]) }}">Login</a>
                <a class="nav-link text-white" href="{{ route('tenant.student.courses.index', ['tenant' => $tenantRouteKey]) }}">Mis cursos</a>
                @if (session()->has('tenant_student_user_id'))
                    <form method="POST" action="{{ route('tenant.student.logout', ['tenant' => $tenantRouteKey]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cerrar sesión</button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm glass-card">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-2">Activar curso por código</h1>
                        <p class="text-muted mb-3">Ingresa tus credenciales y el código asignado por tu administrador.</p>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tenant.activation.store', ['tenant' => $tenantRouteKey]) }}" class="d-grid gap-3">
                            @csrf
                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="form-control" />
                            </div>

                            <div>
                                <label for="password" class="form-label">Contraseña</label>
                                <input id="password" name="password" type="password" required class="form-control" />
                            </div>

                            <div>
                                <label for="code" class="form-label">Código de activación</label>
                                <input id="code" name="code" type="text" value="{{ old('code') }}" required class="form-control text-uppercase" />
                            </div>

                            <button type="submit" class="btn btn-primary">Activar curso</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
