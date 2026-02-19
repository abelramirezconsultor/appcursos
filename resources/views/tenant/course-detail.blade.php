<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de curso</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body.game-bg {
            background: linear-gradient(180deg, #111827 0%, #1e3a8a 40%, #f8fafc 40%, #f8fafc 100%);
            min-height: 100vh;
        }
        .stat-card {
            border-left: 4px solid #6366f1;
        }
    </style>
</head>
<body class="game-bg">
    <nav class="navbar navbar-expand-lg bg-dark navbar-dark border-bottom border-secondary">
        <div class="container">
            <span class="navbar-brand fw-semibold">🏅 {{ tenant('name') ?? 'Plataforma' }}</span>
            <div class="d-flex gap-3 align-items-center">
                <a class="nav-link text-white" href="{{ route('tenant.student.courses.index', ['tenant' => $tenantRouteKey]) }}">Mis cursos</a>
                <a class="nav-link text-white" href="{{ route('tenant.activation.create', ['tenant' => $tenantRouteKey]) }}">Activar código</a>
                <form method="POST" action="{{ route('tenant.student.logout', ['tenant' => $tenantRouteKey]) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3"><div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted">XP</div><div class="h5 mb-0">{{ $gamificationStat?->xp_total ?? 0 }}</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted">Nivel</div><div class="h5 mb-0">{{ $gamificationStat?->level ?? 1 }}</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted">Racha</div><div class="h5 mb-0">{{ $gamificationStat?->streak_days ?? 0 }} días</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card shadow-sm stat-card"><div class="card-body"><div class="small text-muted">Lecciones completadas</div><div class="h5 mb-0">{{ $gamificationStat?->lessons_completed ?? 0 }}</div></div></div></div>
        </div>

        @if ($badges->isNotEmpty())
            <div class="alert alert-success py-2 mb-3">
                <strong>Insignias:</strong>
                @foreach ($badges as $badge)
                    <span class="badge text-bg-success ms-1">{{ $badge->name }}</span>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3 text-white">
            <div>
                <a href="{{ route('tenant.student.courses.index', ['tenant' => $tenantRouteKey]) }}" class="small text-white-50">← Volver a mis cursos</a>
                <h1 class="h2 mt-2 mb-1">{{ $enrollment->course?->title ?? 'Curso' }}</h1>
                <p class="text-white-50 mb-0">{{ $enrollment->course?->description ?: 'Curso con lecciones en video cortas.' }}</p>
            </div>
            <div class="small text-white-50">
                <div><strong>Progreso:</strong> {{ $progressPercent }}%</div>
                <div><strong>Lecciones:</strong> {{ count($completedLessonIds) }} / {{ $lessons->count() }}</div>
                <div><strong>Estado:</strong> {{ $isCompleted ? 'Completado' : 'En curso' }}</div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="progress mb-4" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $progressPercent }}%"></div>
        </div>

        @if ($selectedLesson)
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5">Lección actual: {{ $selectedLesson->title }}</h2>

                            <div class="ratio ratio-16x9 border rounded mb-3 bg-white">
                                @if ($selectedVideoEmbedUrl)
                                    <iframe src="{{ $selectedVideoEmbedUrl }}" title="Video de lección" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                @else
                                    <div class="d-flex align-items-center justify-content-center text-muted">Esta lección aún no tiene video asignado.</div>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <p class="small text-muted mb-0">Videos cortos recomendados: 3-10 minutos por lección.</p>
                                <form method="POST" action="{{ route('tenant.student.lessons.complete', ['tenant' => $tenantRouteKey, 'enrollment' => $enrollment->id, 'lesson' => $selectedLesson->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Marcar como completada</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h6">Lecciones</h3>
                            <div class="list-group">
                                @foreach ($lessons as $lesson)
                                    @php
                                        $isLessonDone = in_array((int) $lesson->id, $completedLessonIds, true);
                                        $isCurrent = (int) $selectedLesson->id === (int) $lesson->id;
                                    @endphp
                                    <a href="{{ route('tenant.student.courses.show', ['tenant' => $tenantRouteKey, 'enrollment' => $enrollment->id, 'lesson' => $lesson->id]) }}" class="list-group-item list-group-item-action {{ $isCurrent ? 'active' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <span>{{ $lesson->title }}</span>
                                            <span class="badge {{ $isLessonDone ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $isLessonDone ? '✓' : 'Pendiente' }}</span>
                                        </div>
                                        <small class="d-block mt-1 {{ $isCurrent ? 'text-white-50' : 'text-muted' }}">XP: {{ $lesson->xp_reward }}</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning">Este curso aún no tiene lecciones cargadas.</div>
        @endif
    </main>
</body>
</html>
