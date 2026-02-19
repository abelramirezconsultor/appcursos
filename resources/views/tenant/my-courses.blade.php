<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis cursos</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.game-bg {
            background: linear-gradient(180deg, #0b1120 0%, #102a43 35%, #f8fafc 35%, #f8fafc 100%);
            min-height: 100vh;
        }
        .hero-chip {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            padding: .35rem .75rem;
            font-size: .8rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="game-bg">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark border-bottom border-secondary">
        <div class="container">
            <span class="navbar-brand fw-semibold">🏆 {{ tenant('name') ?? 'Plataforma' }}</span>
            <div class="d-flex gap-3 align-items-center">
                <a class="nav-link text-white" href="{{ route('tenant.activation.create', ['tenant' => $tenantRouteKey]) }}">Activar código</a>
                <form method="POST" action="{{ route('tenant.student.logout', ['tenant' => $tenantRouteKey]) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <span class="hero-chip">🎯 Progreso activo</span>
            <span class="hero-chip">⚡ XP en tiempo real</span>
            <span class="hero-chip">🔥 Racha diaria</span>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4 text-white">
            <div>
                <h1 class="h3 mb-1">Mis cursos</h1>
                <p class="text-white-50 mb-0">Alumno: {{ $student->name }} ({{ $student->email }})</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">XP total</div><div class="h4 mb-0">{{ $gamificationStat?->xp_total ?? 0 }}</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Nivel</div><div class="h4 mb-0">{{ $gamificationStat?->level ?? 1 }}</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Racha</div><div class="h4 mb-0">{{ $gamificationStat?->streak_days ?? 0 }} días</div></div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">Insignias</div><div class="h4 mb-0">{{ $badges->count() }}</div></div></div>
            </div>
        </div>

        @if ($badges->isNotEmpty())
            <div class="card mb-4 border-success-subtle">
                <div class="card-body">
                    <h6 class="mb-2">Insignias desbloqueadas</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($badges as $badge)
                            <span class="badge rounded-pill text-bg-success">{{ $badge->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3">
            @forelse ($enrollments as $enrollment)
                <div class="col-12 col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h5 class="card-title mb-1">{{ $enrollment->course?->title ?? 'Curso' }}</h5>
                                    <p class="text-muted small mb-1">Matrícula: {{ $enrollment->status }}</p>
                                    <p class="text-muted small mb-0">Curso: {{ $enrollment->course?->status ?? 'n/a' }}</p>
                                </div>
                                <span class="badge text-bg-primary">{{ $enrollment->progress?->progress_percent ?? 0 }}%</span>
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('tenant.student.courses.show', ['tenant' => $tenantRouteKey, 'enrollment' => $enrollment->id]) }}" class="btn btn-primary btn-sm">Ver curso y lecciones</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                @if (($assignedCodes ?? collect())->isEmpty())
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">No tienes cursos activos todavía. Solicita un código y actívalo en esta plataforma.</div>
                    </div>
                @endif
            @endforelse
        </div>

        @if (($assignedCodes ?? collect())->isNotEmpty())
            <div class="card mt-4 border-warning">
                <div class="card-body">
                    <h5 class="card-title">Cursos asignados pendientes de activación</h5>
                    <p class="text-muted">Estos cursos ya fueron asignados por tu administrador.</p>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($assignedCodes as $assigned)
                            <li class="list-group-item">
                                <div class="fw-semibold">{{ $assigned['course_title'] ?? 'Curso' }} ({{ $assigned['course_status'] ?? 'n/a' }})</div>
                                <div class="small text-muted">Código: {{ $assigned['code'] }} | Usos: {{ $assigned['uses'] }} | {{ $assigned['is_active'] ? 'Activo' : 'Inactivo' }}</div>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('tenant.activation.create', ['tenant' => $tenantRouteKey]) }}" class="btn btn-outline-primary btn-sm">Ir a activar código</a>
                </div>
            </div>
        @endif
    </main>
</body>
</html>
