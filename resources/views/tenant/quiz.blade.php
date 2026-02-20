<!DOCTYPE html>
<html lang="es">
@php
    $tenantName = (string) (tenant('name') ?? 'Plataforma');
    $tenantPrimaryColor = (string) (tenant('primary_color') ?? '#2563eb');
    if (!preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $tenantPrimaryColor)) {
        $tenantPrimaryColor = '#2563eb';
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
    <title>Quiz</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ filemtime(public_path('favicon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --tenant-primary: {{ $tenantPrimaryColor }};
        }
        .btn-primary {
            background-color: var(--tenant-primary);
            border-color: var(--tenant-primary);
        }
    </style>
</head>
<body class="bg-light">
    <main class="container py-4">
        <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
            @if ($tenantLogoUrl)
                <img src="{{ $tenantLogoUrl }}" alt="Logo {{ $tenantName }}" style="height:24px; width:auto; max-width:100px; object-fit:contain;" />
            @endif
            <span>{{ $tenantName }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">{{ $quiz->title }}</h1>
            <a href="{{ route('tenant.student.courses.show', ['tenant' => $tenantRouteKey, 'enrollment' => $enrollment->id]) }}" class="btn btn-sm btn-outline-secondary">Volver al curso</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('status'))
            <div class="alert alert-warning">{{ session('status') }}</div>
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

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <p class="mb-2"><strong>Nota mínima:</strong> {{ $quiz->minimum_score }}%</p>
                <p class="mb-2"><strong>Intentos máximos:</strong> {{ $quiz->max_attempts ?? 'Sin límite' }}</p>
                @if ($latestAttempt)
                    <p class="mb-0"><strong>Último intento:</strong> {{ strtoupper($latestAttempt->status) }} - {{ $latestAttempt->score_percent }}%</p>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('tenant.student.quizzes.submit', ['tenant' => $tenantRouteKey, 'enrollment' => $enrollment->id, 'quiz' => $quiz->id]) }}" class="d-grid gap-3">
            @csrf

            @forelse ($quiz->questions as $question)
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="fw-semibold mb-2">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                        @foreach ($question->options as $option)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]" id="q{{ $question->id }}o{{ $option->id }}" value="{{ $option->id }}">
                                <label class="form-check-label" for="q{{ $question->id }}o{{ $option->id }}">{{ $option->option_text }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="alert alert-info">Este quiz aún no tiene preguntas.</div>
            @endforelse

            @if ($quiz->questions->isNotEmpty())
                <button type="submit" class="btn btn-primary">Enviar evaluación</button>
            @endif
        </form>
    </main>
</body>
</html>
