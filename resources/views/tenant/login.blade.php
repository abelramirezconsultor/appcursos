<!DOCTYPE html>
<html lang="es">
@php
    $tenantName = (string) (tenant('name') ?? 'Plataforma');
    $tenantPrimaryColor = (string) (tenant('primary_color') ?? '#1e3a8a');
    if (!preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $tenantPrimaryColor)) {
        $tenantPrimaryColor = '#1e3a8a';
    }
    $tenantLogoPath = (string) (tenant('logo_path') ?? '');
    $tenantLogoUrl = null;
    if ($tenantLogoPath !== '') {
        $tenantLogoUrl = \Illuminate\Support\Str::startsWith($tenantLogoPath, ['http://', 'https://', '/'])
            ? $tenantLogoPath
            : asset($tenantLogoPath);
    }
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login estudiante</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.game-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #0ea5e9 100%);
            min-height: 100vh;
        }
        :root {
            --tenant-primary: {{ $tenantPrimaryColor }};
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .btn-primary {
            background-color: var(--tenant-primary);
            border-color: var(--tenant-primary);
        }
    </style>
</head>
<body class="game-bg">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark border-bottom border-secondary">
        <div class="container">
            <span class="navbar-brand fw-semibold d-inline-flex align-items-center gap-2">
                @if ($tenantLogoUrl)
                    <img src="{{ $tenantLogoUrl }}" alt="Logo {{ $tenantName }}" style="height:30px; width:auto; max-width:120px; object-fit:contain;" />
                @endif
                <span>{{ $tenantName }}</span>
            </span>
            <div class="d-flex gap-3">
                <a class="nav-link text-white" href="{{ route('tenant.activation.create', ['tenant' => $tenantRouteKey]) }}">Activar código</a>
                <a class="nav-link text-white" href="{{ route('tenant.student.courses.index', ['tenant' => $tenantRouteKey]) }}">Mis cursos</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm glass-card">
                    <div class="card-body p-4">
                        <h1 class="h3 mb-2">Ingreso de estudiante</h1>
                        <p class="text-muted mb-4">Accede con tu usuario de esta plataforma para ver tus cursos.</p>

                        @if (session('status'))
                            <div class="alert alert-info">{{ session('status') }}</div>
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

                        @if (!empty($tenantSuspended))
                            <div class="alert alert-warning">
                                Esta plataforma está suspendida temporalmente. Contacta al administrador.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tenant.student.login.store', ['tenant' => $tenantRouteKey]) }}" class="d-grid gap-3">
                            @csrf
                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $prefillEmail ?? '') }}" required class="form-control" />
                            </div>

                            <div>
                                <label for="password" class="form-label">Contraseña</label>
                                <input id="password" name="password" type="password" required class="form-control" />
                            </div>

                            <button type="submit" class="btn btn-primary" @disabled(!empty($tenantSuspended))>Ingresar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
