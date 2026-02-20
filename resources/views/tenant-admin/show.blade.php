<x-app-layout>

    @php
        $currentTab = request()->query('tab', 'metrics');
        if (!in_array($currentTab, ['tracking', 'academy', 'metrics', 'leaderboard', 'heatmap'], true)) {
            $currentTab = 'metrics';
        }

        $academySection = request()->query('section', 'course');
        if (!in_array($academySection, ['course', 'module', 'lesson', 'quiz', 'question'], true)) {
            $academySection = 'course';
        }

        $quizActive = collect($quizzes)->where('is_active', true)->count();
        $quizInactive = max(0, count($quizzes) - $quizActive);
        $barCourseLabels = collect($courseMetrics)->pluck('title')->values()->all();
        $barCourseEnrollments = collect($courseMetrics)->pluck('enrollments_count')->values()->all();
        $barCourseProgress = collect($courseMetrics)->pluck('avg_progress_percent')->values()->all();
        $leaderboardTop = collect($leaderboard)->take(8)->values()->all();

        $maxHeatXp = max(1, (int) collect($leaderboard)->max('xp_total'));
        $maxHeatLessons = max(1, (int) collect($leaderboard)->max('lessons_completed'));
        $maxHeatStreak = max(1, (int) collect($leaderboard)->max('streak_days'));
        $heatColor = function ($value, $max): string {
            $ratio = $max > 0 ? max(0, min(1, ((float) $value) / $max)) : 0;
            $hue = 210 - (210 * $ratio);
            return "hsl({$hue}, 85%, 55%)";
        };

        $heatmapZones = [
            ['label' => 'Spawn: registro', 'value' => (int) $summary['students_count'], 'hint' => 'Altas de estudiantes'],
            ['label' => 'Lobby: códigos', 'value' => (int) $summary['codes_total'], 'hint' => 'Códigos emitidos'],
            ['label' => 'Arena: matrículas', 'value' => (int) $summary['enrollments_count'], 'hint' => 'Inscripciones activas'],
            ['label' => 'Boss room: quizzes', 'value' => (int) count($quizzes), 'hint' => 'Quizzes configurados'],
            ['label' => 'Ruta cursos', 'value' => (int) $summary['courses_count'], 'hint' => 'Cursos activos'],
            ['label' => 'Ruta módulos', 'value' => (int) $summary['modules_count'], 'hint' => 'Módulos creados'],
            ['label' => 'Ruta lecciones', 'value' => (int) $summary['lessons_count'], 'hint' => 'Lecciones disponibles'],
            ['label' => 'Racha diaria', 'value' => (int) collect($leaderboard)->sum('streak_days'), 'hint' => 'Suma de rachas'],
            ['label' => 'XP total', 'value' => (int) collect($leaderboard)->sum('xp_total'), 'hint' => 'XP acumulada'],
            ['label' => 'Insignias', 'value' => (int) $summary['badges_awarded'], 'hint' => 'Badges ganadas'],
            ['label' => 'Canje códigos', 'value' => (int) $summary['codes_used'], 'hint' => 'Códigos usados'],
            ['label' => 'Riesgo fraude', 'value' => (int) $summary['failed_code_attempts'], 'hint' => 'Intentos fallidos'],
        ];
        $maxHeatZone = max(1, (int) collect($heatmapZones)->max('value'));

        $baseTenantUrl = route('tenant-admin.show', $tenant->id);
        $tabUrl = function (string $tab) use ($baseTenantUrl, $academySection): string {
            $params = ['tab' => $tab];
            if ($tab === 'academy') {
                $params['section'] = $academySection;
            }
            return $baseTenantUrl . '?' . http_build_query($params);
        };
        $sectionUrl = function (string $section) use ($baseTenantUrl): string {
            return $baseTenantUrl . '?' . http_build_query(['tab' => 'academy', 'section' => $section]);
        };
    @endphp

    <div class="py-10 min-h-screen gaming-page">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($currentTab === 'tracking')
                <section class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-md p-4">
                        URL de acceso de estudiantes:
                        <a href="{{ route('tenant.student.login', ['tenant' => $tenant->alias]) }}" class="underline break-all">
                            {{ route('tenant.student.login', ['tenant' => $tenant->alias]) }}
                        </a>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-people-fill"></i><span>Students</span></p><p class="text-2xl font-semibold">{{ $summary['students_count'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-journal-bookmark-fill"></i><span>Cursos</span></p><p class="text-2xl font-semibold">{{ $summary['courses_count'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-diagram-3-fill"></i><span>Módulos / Lecciones</span></p><p class="text-2xl font-semibold">{{ $summary['modules_count'] }} / {{ $summary['lessons_count'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-ui-checks-grid"></i><span>Quizzes</span></p><p class="text-2xl font-semibold">{{ count($quizzes) }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">⚔️ Matrículas</p><p class="text-2xl font-semibold">{{ $summary['enrollments_count'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">🏆 Avance promedio</p><p class="text-2xl font-semibold">{{ $summary['avg_progress_percent'] }}%</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">🔥 Insignias</p><p class="text-2xl font-semibold">{{ $summary['badges_awarded'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-shield-fill-check"></i><span>Plan / Estado</span></p><p class="text-lg font-semibold">{{ strtoupper($limits['plan_code']) }} / {{ strtoupper($limits['status']) }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-person-lines-fill"></i><span>Uso usuarios</span></p><p class="text-2xl font-semibold">{{ $limits['users']['used'] }}/{{ $limits['users']['max'] }}</p><p class="text-xs text-gray-500">{{ $limits['users']['percent'] }}%</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-archive-fill"></i><span>Uso cursos</span></p><p class="text-2xl font-semibold">{{ $limits['courses']['used'] }}/{{ $limits['courses']['max'] }}</p><p class="text-xs text-gray-500">{{ $limits['courses']['percent'] }}%</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i><span>Intentos fallidos</span></p><p class="text-2xl font-semibold">{{ $summary['failed_code_attempts'] }}</p></div>
                        <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase flex items-center gap-2"><i class="bi bi-bullseye"></i><span>Conversión</span></p><p class="text-2xl font-semibold">{{ $summary['code_conversion_percent'] }}%</p><p class="text-xs text-gray-500">{{ $summary['codes_used'] }}/{{ $summary['codes_total'] }} usados</p></div>
                    </div>

                    <div id="quick-users" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white shadow-sm rounded-lg p-6 border border-indigo-100">
                            <h3 class="font-semibold text-lg mb-4">👤 Crear usuario normal (student)</h3>
                            <form method="POST" action="{{ route('tenant-admin.students.store', $tenant->id) }}" class="space-y-4">
                                @csrf
                                <div><x-input-label for="student_name" :value="__('Nombre')" /><x-text-input id="student_name" class="block mt-1 w-full" type="text" name="name" required /></div>
                                <div><x-input-label for="student_email" :value="__('Email')" /><x-text-input id="student_email" class="block mt-1 w-full" type="email" name="email" required /></div>
                                <div><x-input-label for="student_password" :value="__('Contraseña inicial')" /><x-text-input id="student_password" class="block mt-1 w-full" type="password" name="password" required /></div>
                                <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear usuario normal</button>
                            </form>
                        </div>

                        <div id="quick-codes" class="bg-white shadow-sm rounded-lg p-6 border border-indigo-100">
                            <h3 class="font-semibold text-lg mb-4">🔑 Asignar código de activación</h3>
                            <form method="POST" action="{{ route('tenant-admin.codes.store', $tenant->id) }}" class="grid grid-cols-1 gap-4">
                                @csrf
                                <div>
                                    <x-input-label for="code_course_id" :value="__('Curso')" />
                                    <select id="code_course_id" name="course_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                        <option value="">Seleccione</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="code_student_id" :value="__('Usuario normal')" />
                                    <select id="code_student_id" name="assigned_user_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                        <option value="">Seleccione</option>
                                        @foreach ($students as $student)
                                            <option value="{{ $student['id'] }}">{{ $student['name'] }} ({{ $student['email'] }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div><x-input-label for="code_value" :value="__('Código (opcional)')" /><x-text-input id="code_value" class="block mt-1 w-full" type="text" name="code" /></div>
                                    <div><x-input-label for="code_expires" :value="__('Expira (opcional)')" /><x-text-input id="code_expires" class="block mt-1 w-full" type="datetime-local" name="expires_at" /></div>
                                </div>
                                <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Asignar código</button>
                            </form>
                        </div>
                    </div>
                </section>
            @elseif ($currentTab === 'academy')
                <div class="space-y-6">
                    <div class="bg-white shadow-sm rounded-lg p-4 border border-indigo-100">
                        <h3 class="font-semibold text-lg mb-3">🕹️ My academy</h3>
                        <p class="text-sm text-gray-600 mb-3">Pestañas horizontales para crear contenido.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $sectionUrl('course') }}" class="px-3 py-2 rounded-md font-medium {{ $academySection === 'course' ? 'academy-tab-active' : 'academy-tab-inactive' }}">📘 Curso</a>
                            <a href="{{ $sectionUrl('module') }}" class="px-3 py-2 rounded-md font-medium {{ $academySection === 'module' ? 'academy-tab-active' : 'academy-tab-inactive' }}">🧩 Módulo</a>
                            <a href="{{ $sectionUrl('lesson') }}" class="px-3 py-2 rounded-md font-medium {{ $academySection === 'lesson' ? 'academy-tab-active' : 'academy-tab-inactive' }}">🎬 Lección</a>
                            <a href="{{ $sectionUrl('quiz') }}" class="px-3 py-2 rounded-md font-medium {{ $academySection === 'quiz' ? 'academy-tab-active' : 'academy-tab-inactive' }}">📝 Quiz</a>
                            <a href="{{ $sectionUrl('question') }}" class="px-3 py-2 rounded-md font-medium {{ $academySection === 'question' ? 'academy-tab-active' : 'academy-tab-inactive' }}">❓ Pregunta</a>
                        </div>
                    </div>

                    <section class="space-y-6">
                        <div class="bg-white shadow-sm rounded-lg p-6 border border-indigo-100">
                            @if ($academySection === 'course')
                                <h3 class="font-semibold text-lg mb-4">📘 Crear curso</h3>
                                <form method="POST" action="{{ route('tenant-admin.courses.store', $tenant->id) }}" class="space-y-4">
                                    @csrf
                                    <div><x-input-label for="course_title" :value="__('Título')" /><x-text-input id="course_title" class="block mt-1 w-full" type="text" name="title" required /></div>
                                    <div><x-input-label for="course_description" :value="__('Descripción')" /><textarea id="course_description" name="description" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea></div>
                                    <div>
                                        <x-input-label for="course_status" :value="__('Estado')" />
                                        <select id="course_status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                            <option value="draft">Draft</option>
                                            <option value="published">Published</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear curso</button>
                                </form>
                            @elseif ($academySection === 'module')
                                <h3 class="font-semibold text-lg mb-4">🧩 Crear módulo</h3>
                                <form method="POST" action="{{ route('tenant-admin.modules.store', $tenant->id) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <x-input-label for="module_course_id" :value="__('Curso')" />
                                        <select id="module_course_id" name="course_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                            <option value="">Seleccione</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><x-input-label for="module_title" :value="__('Título del módulo')" /><x-text-input id="module_title" class="block mt-1 w-full" type="text" name="title" required /></div>
                                    <div><x-input-label for="module_position" :value="__('Posición (opcional)')" /><x-text-input id="module_position" class="block mt-1 w-full" type="number" min="1" name="position" /></div>
                                    <div>
                                        <x-input-label for="module_prerequisite_module_id" :value="__('Módulo prerequisito (opcional)')" />
                                        <select id="module_prerequisite_module_id" name="prerequisite_module_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                            <option value="">Sin prerequisito</option>
                                            @foreach ($modules as $module)
                                                <option value="{{ $module['id'] }}">{{ $module['course_title'] ?? 'Curso' }} / {{ $module['title'] }}</option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">Tip: selecciona un módulo del mismo curso para ordenar la ruta de aprendizaje.</p>
                                    </div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_prerequisite_mandatory" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ms-2 text-sm text-gray-600">Prerequisito obligatorio (si no se cumple, el módulo queda bloqueado)</span>
                                    </label>
                                    <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear módulo</button>
                                </form>

                                @if (count($modules) > 0)
                                    <div class="mt-8">
                                        <h4 class="font-semibold text-base mb-3">⚙️ Mini panel: prerequisitos existentes</h4>
                                        <div class="space-y-3">
                                            @foreach ($modules as $moduleRow)
                                                @php
                                                    $sameCourseModules = collect($modules)
                                                        ->where('course_id', $moduleRow['course_id'])
                                                        ->where('id', '!=', $moduleRow['id'])
                                                        ->values();
                                                @endphp
                                                <form method="POST" action="{{ route('tenant-admin.modules.prerequisite.update', ['tenant' => $tenant->id, 'module' => $moduleRow['id']]) }}" class="border border-indigo-100 rounded-lg p-4 bg-indigo-50/40">
                                                    @csrf
                                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                                        <div class="md:col-span-4">
                                                            <p class="text-sm font-semibold text-slate-800">{{ $moduleRow['course_title'] ?? 'Curso' }} / {{ $moduleRow['title'] }}</p>
                                                            <p class="text-xs text-slate-500">Posición: {{ $moduleRow['position'] }}</p>
                                                        </div>
                                                        <div class="md:col-span-4">
                                                            <x-input-label :value="__('Prerequisito')" />
                                                            <select name="prerequisite_module_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                                                <option value="">Sin prerequisito</option>
                                                                @foreach ($sameCourseModules as $candidate)
                                                                    <option value="{{ $candidate['id'] }}" @selected((int) ($moduleRow['prerequisite_module_id'] ?? 0) === (int) $candidate['id'])>
                                                                        {{ $candidate['title'] }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <label class="inline-flex items-center">
                                                                <input type="checkbox" name="is_prerequisite_mandatory" value="1" @checked((bool) ($moduleRow['is_prerequisite_mandatory'] ?? false)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                                <span class="ms-2 text-sm text-gray-700">Obligatorio</span>
                                                            </label>
                                                        </div>
                                                        <div class="md:col-span-2">
                                                            <button type="submit" class="g-action-btn g-action-accept w-full justify-center"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Guardar</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @elseif ($academySection === 'lesson')
                                <h3 class="font-semibold text-lg mb-4">🎬 Crear lección</h3>
                                <form method="POST" action="{{ route('tenant-admin.lessons.store', $tenant->id) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <x-input-label for="lesson_module_id" :value="__('Módulo')" />
                                        <select id="lesson_module_id" name="module_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                            <option value="">Seleccione</option>
                                            @foreach ($modules as $module)
                                                <option value="{{ $module['id'] }}">{{ $module['course_title'] ?? 'Curso' }} / {{ $module['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><x-input-label for="lesson_title" :value="__('Título de lección')" /><x-text-input id="lesson_title" class="block mt-1 w-full" type="text" name="title" required /></div>
                                    <div><x-input-label for="lesson_video_url" :value="__('URL de video')" /><x-text-input id="lesson_video_url" class="block mt-1 w-full" type="url" name="video_url" /></div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div><x-input-label for="lesson_duration_seconds" :value="__('Duración seg')" /><x-text-input id="lesson_duration_seconds" class="block mt-1 w-full" type="number" min="30" max="900" name="duration_seconds" /></div>
                                        <div><x-input-label for="lesson_position" :value="__('Posición')" /><x-text-input id="lesson_position" class="block mt-1 w-full" type="number" min="1" name="position" /></div>
                                        <div><x-input-label for="lesson_xp_reward" :value="__('XP')" /><x-text-input id="lesson_xp_reward" class="block mt-1 w-full" type="number" min="0" name="xp_reward" value="0" /></div>
                                    </div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_preview" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ms-2 text-sm text-gray-600">Vista previa</span>
                                    </label>
                                    <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear lección</button>
                                </form>
                            @elseif ($academySection === 'quiz')
                                <h3 class="font-semibold text-lg mb-4">📝 Crear quiz</h3>
                                <form method="POST" action="{{ route('tenant-admin.quizzes.store', $tenant->id) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <x-input-label for="quiz_course_id" :value="__('Curso')" />
                                        <select id="quiz_course_id" name="course_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                            <option value="">Seleccione</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="quiz_module_id" :value="__('Módulo (opcional)')" />
                                        <select id="quiz_module_id" name="module_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                                            <option value="">Sin módulo específico</option>
                                            @foreach ($modules as $module)
                                                <option value="{{ $module['id'] }}">{{ $module['course_title'] ?? 'Curso' }} / {{ $module['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><x-input-label for="quiz_title" :value="__('Título del quiz')" /><x-text-input id="quiz_title" class="block mt-1 w-full" type="text" name="title" required /></div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div><x-input-label for="quiz_minimum_score" :value="__('Nota mínima %')" /><x-text-input id="quiz_minimum_score" class="block mt-1 w-full" type="number" min="0" max="100" name="minimum_score" value="70" required /></div>
                                        <div><x-input-label for="quiz_max_attempts" :value="__('Intentos máximos')" /><x-text-input id="quiz_max_attempts" class="block mt-1 w-full" type="number" min="1" max="20" name="max_attempts" /></div>
                                        <div class="flex items-end">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                <span class="ms-2 text-sm text-gray-600">Activo</span>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear quiz</button>
                                </form>
                            @else
                                <h3 class="font-semibold text-lg mb-4">❓ Crear pregunta de quiz</h3>
                                <form method="POST" action="{{ route('tenant-admin.quiz-questions.store', $tenant->id) }}" class="space-y-4">
                                    @csrf
                                    <div>
                                        <x-input-label for="question_quiz_id" :value="__('Quiz')" />
                                        <select id="question_quiz_id" name="quiz_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                            <option value="">Seleccione</option>
                                            @foreach ($quizzes as $quiz)
                                                <option value="{{ $quiz['id'] }}">{{ $quiz['title'] }} ({{ $quiz['course_title'] ?? 'Curso' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><x-input-label for="question_text" :value="__('Pregunta')" /><textarea id="question_text" name="question_text" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required></textarea></div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div><x-input-label for="option_a" :value="__('Opción A')" /><x-text-input id="option_a" class="block mt-1 w-full" type="text" name="option_a" required /></div>
                                        <div><x-input-label for="option_b" :value="__('Opción B')" /><x-text-input id="option_b" class="block mt-1 w-full" type="text" name="option_b" required /></div>
                                        <div><x-input-label for="option_c" :value="__('Opción C')" /><x-text-input id="option_c" class="block mt-1 w-full" type="text" name="option_c" required /></div>
                                        <div><x-input-label for="option_d" :value="__('Opción D')" /><x-text-input id="option_d" class="block mt-1 w-full" type="text" name="option_d" required /></div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <x-input-label for="correct_option" :value="__('Correcta')" />
                                            <select id="correct_option" name="correct_option" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                                <option value="a">A</option>
                                                <option value="b">B</option>
                                                <option value="c">C</option>
                                                <option value="d">D</option>
                                            </select>
                                        </div>
                                        <div><x-input-label for="question_points" :value="__('Puntos')" /><x-text-input id="question_points" class="block mt-1 w-full" type="number" min="1" max="100" name="points" value="1" /></div>
                                        <div><x-input-label for="question_position" :value="__('Posición')" /><x-text-input id="question_position" class="block mt-1 w-full" type="number" min="1" name="position" /></div>
                                    </div>
                                    <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear pregunta</button>
                                </form>
                            @endif
                        </div>
                    </section>
                </div>
            @elseif ($currentTab === 'metrics')
                <section class="space-y-6">
                    <div id="metrics-zone" class="bg-white shadow-sm rounded-lg p-6 border border-sky-100">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <h3 class="font-semibold text-lg">🧠 Panel de métricas gaming</h3>
                            <a href="{{ route('tenant-admin.reports.export-csv', ['tenant' => $tenant->id]) }}" class="g-action-btn g-action-report text-sm">
                                <i class="bi bi-square-fill" aria-hidden="true"></i>
                                <span>Exportar CSV</span>
                            </a>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 overflow-hidden">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">📊 Matrículas por curso</h4>
                                <div class="relative h-64 w-full overflow-hidden"><canvas id="coursesBarChart" class="w-full h-full"></canvas></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 overflow-hidden">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">🥧 Códigos usados vs no usados</h4>
                                <div class="relative h-64 w-full overflow-hidden"><canvas id="codesPieChart" class="w-full h-full"></canvas></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 overflow-hidden">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">📝 Estado de quizzes</h4>
                                <div class="relative h-64 w-full overflow-hidden"><canvas id="quizStatusChart" class="w-full h-full"></canvas></div>
                            </div>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 overflow-hidden">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">🏆 Top XP leaderboard</h4>
                                <div class="relative h-64 w-full overflow-hidden"><canvas id="leaderboardChart" class="w-full h-full"></canvas></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">📈 Métricas por curso</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Curso</th><th class="py-2 pr-3">Estado</th><th class="py-2 pr-3">Módulos</th><th class="py-2 pr-3">Lecciones</th><th class="py-2 pr-3">Matrículas</th><th class="py-2 pr-3">Avance prom.</th><th class="py-2 pr-3">Acciones</th></tr></thead>
                                <tbody>
                                    @forelse ($courseMetrics as $metric)
                                        <tr class="border-b last:border-0">
                                            <td class="py-2 pr-3">{{ $metric['title'] }}</td>
                                            <td class="py-2 pr-3">{{ $metric['status'] }}</td>
                                            <td class="py-2 pr-3">{{ $metric['modules_count'] }}</td>
                                            <td class="py-2 pr-3">{{ $metric['lessons_count'] }}</td>
                                            <td class="py-2 pr-3">{{ $metric['enrollments_count'] }}</td>
                                            <td class="py-2 pr-3">{{ $metric['avg_progress_percent'] }}%</td>
                                            <td class="py-2 pr-3">
                                                <div class="flex items-center gap-3">
                                                    <form method="POST" action="{{ route('tenant-admin.courses.toggle-status', ['tenant' => $tenant->id, 'course' => $metric['id']]) }}">
                                                        @csrf
                                                        <button type="submit" class="text-xs underline {{ $metric['status'] === 'published' ? 'text-amber-700' : 'text-green-700' }}">
                                                            {{ $metric['status'] === 'published' ? 'Borrador' : 'Publicar' }}
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('tenant-admin.courses.destroy', ['tenant' => $tenant->id, 'course' => $metric['id']]) }}" onsubmit="return confirm('¿Eliminar este curso? Esta acción no se puede deshacer.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs underline text-red-700">Eliminar</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="py-3 text-gray-500">Sin métricas aún.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">🧪 Quizzes</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Quiz</th><th class="py-2 pr-3">Curso/Módulo</th><th class="py-2 pr-3">Nota mínima</th><th class="py-2 pr-3">Intentos</th><th class="py-2 pr-3">Preguntas</th><th class="py-2 pr-3">Estado</th></tr></thead>
                                <tbody>
                                    @forelse ($quizzes as $quiz)
                                        <tr class="border-b last:border-0">
                                            <td class="py-2 pr-3">{{ $quiz['title'] }}</td>
                                            <td class="py-2 pr-3">{{ $quiz['course_title'] }} @if($quiz['module_title']) / {{ $quiz['module_title'] }} @endif</td>
                                            <td class="py-2 pr-3">{{ $quiz['minimum_score'] }}%</td>
                                            <td class="py-2 pr-3">{{ $quiz['max_attempts'] ?? '∞' }}</td>
                                            <td class="py-2 pr-3">{{ $quiz['questions_count'] }}</td>
                                            <td class="py-2 pr-3">{{ $quiz['is_active'] ? 'Activo' : 'Inactivo' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="py-3 text-gray-500">Sin quizzes aún.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">💹 Conversión por curso (códigos)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Curso</th><th class="py-2 pr-3">Total</th><th class="py-2 pr-3">Usados</th><th class="py-2 pr-3">No usados</th><th class="py-2 pr-3">Conversión</th></tr></thead>
                                <tbody>
                                    @forelse ($codeConversionByCourse as $row)
                                        <tr class="border-b last:border-0">
                                            <td class="py-2 pr-3">{{ $row['course_title'] }}</td>
                                            <td class="py-2 pr-3">{{ $row['codes_total'] }}</td>
                                            <td class="py-2 pr-3">{{ $row['codes_used'] }}</td>
                                            <td class="py-2 pr-3">{{ $row['codes_unused'] }}</td>
                                            <td class="py-2 pr-3">{{ $row['conversion_percent'] }}%</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-3 text-gray-500">Sin datos de códigos aún.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @elseif ($currentTab === 'heatmap')
                <section class="space-y-6">
                    <div class="bg-white shadow-sm rounded-lg p-6 border border-rose-100">
                        <h4 class="font-semibold mb-2">🗺️ Heatmap de actividad de jugadores</h4>
                        <p class="text-sm text-gray-600 mb-4">Vista tipo mapa: zonas frías = baja actividad, zonas cálidas = alta actividad.</p>

                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach ($heatmapZones as $zone)
                                @php
                                    $zoneColor = $heatColor($zone['value'], $maxHeatZone);
                                @endphp
                                <div class="rounded-lg border border-slate-200 p-4 text-white shadow-sm" style="background: linear-gradient(160deg, {{ $zoneColor }} 0%, rgba(15,23,42,0.75) 100%);">
                                    <p class="text-xs uppercase tracking-wide opacity-90">{{ $zone['label'] }}</p>
                                    <p class="mt-2 text-2xl font-bold">{{ $zone['value'] }}</p>
                                    <p class="text-xs mt-1 opacity-90">{{ $zone['hint'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-gray-600">
                            <span class="inline-block h-3 w-8 rounded" style="background-color: hsl(210, 85%, 55%);"></span> Baja
                            <span class="inline-block h-3 w-8 rounded" style="background-color: hsl(120, 85%, 55%);"></span> Media
                            <span class="inline-block h-3 w-8 rounded" style="background-color: hsl(30, 85%, 55%);"></span> Alta
                            <span class="inline-block h-3 w-8 rounded" style="background-color: hsl(0, 85%, 55%);"></span> Muy alta
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6 border border-rose-100">
                        <h4 class="font-semibold mb-3">🎮 Intensidad por jugador</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border-separate border-spacing-y-2">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2 pr-3">Jugador</th>
                                        <th class="py-2 pr-3">XP</th>
                                        <th class="py-2 pr-3">Racha</th>
                                        <th class="py-2 pr-3">Lecciones</th>
                                        <th class="py-2 pr-3">Nivel</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($leaderboard as $entry)
                                        <tr class="align-middle">
                                            <td class="py-2 pr-3">
                                                <span class="font-medium">{{ $entry['name'] }}</span>
                                                <div class="text-xs text-gray-500">{{ $entry['email'] }}</div>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <span class="inline-flex min-w-[92px] justify-center rounded-md px-3 py-2 text-white font-semibold" style="background-color: {{ $heatColor($entry['xp_total'], $maxHeatXp) }};">{{ $entry['xp_total'] }}</span>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <span class="inline-flex min-w-[92px] justify-center rounded-md px-3 py-2 text-white font-semibold" style="background-color: {{ $heatColor($entry['streak_days'], $maxHeatStreak) }};">{{ $entry['streak_days'] }} días</span>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <span class="inline-flex min-w-[92px] justify-center rounded-md px-3 py-2 text-white font-semibold" style="background-color: {{ $heatColor($entry['lessons_completed'], $maxHeatLessons) }};">{{ $entry['lessons_completed'] }}</span>
                                            </td>
                                            <td class="py-2 pr-3">{{ $entry['level'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-3 text-gray-500">Sin actividad suficiente para generar heatmap.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @else
                <section class="space-y-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <h4 class="font-semibold">📅 Ranking por periodo</h4>
                            <form method="GET" action="{{ route('tenant-admin.show', $tenant->id) }}" class="flex flex-wrap items-end gap-2">
                                <input type="hidden" name="tab" value="leaderboard">
                                <div>
                                    <label for="preset" class="text-sm text-gray-600">Preset</label>
                                    <select id="preset" name="preset" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="current_week" @selected(($weeklyPreset ?? 'current_week') === 'current_week')>Semana actual</option>
                                        <option value="last_7_days" @selected(($weeklyPreset ?? 'current_week') === 'last_7_days')>Últimos 7 días</option>
                                        <option value="last_30_days" @selected(($weeklyPreset ?? 'current_week') === 'last_30_days')>Últimos 30 días</option>
                                        <option value="this_month" @selected(($weeklyPreset ?? 'current_week') === 'this_month')>Este mes</option>
                                        <option value="custom" @selected(($weeklyPreset ?? 'current_week') === 'custom')>Personalizado</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="start_date" class="text-sm text-gray-600">Fecha inicial</label>
                                    <input id="start_date" name="start_date" type="date" value="{{ $weeklySelectedStartDate }}" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                                </div>
                                <div>
                                    <label for="end_date" class="text-sm text-gray-600">Fecha final</label>
                                    <input id="end_date" name="end_date" type="date" value="{{ $weeklySelectedEndDate }}" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
                                </div>
                                <button type="submit" class="g-action-btn g-action-report text-sm"><i class="bi bi-square-fill" aria-hidden="true"></i>Aplicar</button>
                            </form>
                        </div>

                        <p class="text-sm text-gray-500 mb-3">Rango: {{ \Illuminate\Support\Carbon::parse($weeklySelectedStartDate)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($weeklySelectedEndDate)->format('d/m/Y') }} <span class="text-xs">(TZ: {{ $weeklyTimezone ?? 'America/Bogota' }})</span></p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b">
                                        <th class="py-2 pr-3">Alumno</th>
                                        <th class="py-2 pr-3">XP del periodo</th>
                                        <th class="py-2 pr-3">Lecciones del periodo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($weeklyLeaderboard as $entry)
                                        @php $rank = $loop->iteration; @endphp
                                        <tr class="border-b last:border-0">
                                            <td class="py-2 pr-3">
                                                @if ($rank === 1) 🥇 @elseif ($rank === 2) 🥈 @elseif ($rank === 3) 🥉 @else #{{ $rank }} @endif
                                                {{ $entry['name'] }}
                                                <div class="text-xs text-gray-500">{{ $entry['email'] }}</div>
                                            </td>
                                            <td class="py-2 pr-3">{{ $entry['xp_earned'] }}</td>
                                            <td class="py-2 pr-3">{{ $entry['lessons_completed'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="py-3 text-gray-500">Sin actividad en la semana seleccionada.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">🏅 Leaderboard (Gamificación)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b">
                                        <th class="py-2 pr-3">Alumno</th>
                                        <th class="py-2 pr-3">XP</th>
                                        <th class="py-2 pr-3">Nivel</th>
                                        <th class="py-2 pr-3">Racha</th>
                                        <th class="py-2 pr-3">Lecciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($leaderboard as $entry)
                                        @php $rank = $loop->iteration; @endphp
                                        <tr class="border-b last:border-0">
                                            <td class="py-2 pr-3">
                                                @if ($rank === 1) 🥇 @elseif ($rank === 2) 🥈 @elseif ($rank === 3) 🥉 @else #{{ $rank }} @endif
                                                {{ $entry['name'] }}
                                                <div class="text-xs text-gray-500">{{ $entry['email'] }}</div>
                                            </td>
                                            <td class="py-2 pr-3">{{ $entry['xp_total'] }}</td>
                                            <td class="py-2 pr-3">{{ $entry['level'] }}</td>
                                            <td class="py-2 pr-3">{{ $entry['streak_days'] }} días</td>
                                            <td class="py-2 pr-3">{{ $entry['lessons_completed'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-3 text-gray-500">Sin actividad gamificada todavía.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h4 class="font-semibold mb-3">👥 Usuarios normales</h4>
                            <ul class="space-y-2 text-sm">
                                @forelse ($students as $student)
                                    <li>{{ $student['name'] }} - {{ $student['email'] }}</li>
                                @empty
                                    <li>Sin usuarios aún.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h4 class="font-semibold mb-3">📚 Cursos</h4>
                            <ul class="space-y-2 text-sm">
                                @forelse ($courses as $course)
                                    <li class="border rounded-md p-2">
                                        <div>{{ $course['title'] }} ({{ $course['status'] }})</div>
                                        <div class="mt-1 flex items-center gap-3">
                                            <form method="POST" action="{{ route('tenant-admin.courses.toggle-status', ['tenant' => $tenant->id, 'course' => $course['id']]) }}">
                                                @csrf
                                                <button type="submit" class="text-xs underline {{ $course['status'] === 'published' ? 'text-amber-700' : 'text-green-700' }}">
                                                    {{ $course['status'] === 'published' ? 'Borrador' : 'Publicar' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('tenant-admin.courses.destroy', ['tenant' => $tenant->id, 'course' => $course['id']]) }}" onsubmit="return confirm('¿Eliminar este curso? Esta acción no se puede deshacer.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs underline text-red-700">Eliminar</button>
                                            </form>
                                        </div>
                                    </li>
                                @empty
                                    <li>Sin cursos aún.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="bg-white shadow-sm rounded-lg p-6">
                            <h4 class="font-semibold mb-3">🎟️ Códigos recientes</h4>
                            <ul class="space-y-2 text-sm">
                                @forelse ($codes as $code)
                                    <li class="border rounded-md p-2">
                                        <div>{{ $code['code'] }} - {{ $code['course']['title'] ?? 'Curso' }}</div>
                                        <div class="text-xs text-gray-600">{{ $code['assigned_user']['email'] ?? 'Sin usuario' }} | usos {{ $code['uses_count'] }}/{{ $code['max_uses'] }}</div>
                                        <form method="POST" action="{{ route('tenant-admin.codes.toggle', ['tenant' => $tenant->id, 'code' => $code['id']]) }}" class="mt-1">
                                            @csrf
                                            <button type="submit" class="text-xs underline {{ $code['is_active'] ? 'text-red-700' : 'text-green-700' }}">{{ $code['is_active'] ? 'Desactivar' : 'Reactivar' }}</button>
                                        </form>
                                    </li>
                                @empty
                                    <li>Sin códigos aún.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">🕒 Redenciones recientes</h4>
                        <ul class="space-y-2 text-sm">
                            @forelse ($recentRedemptions as $redemption)
                                <li>
                                    {{ $redemption['activation_code']['code'] ?? 'Código' }}
                                    - {{ $redemption['activation_code']['course']['title'] ?? 'Curso' }}
                                    - {{ $redemption['user']['email'] ?? 'Usuario' }}
                                    - {{ \Illuminate\Support\Carbon::parse($redemption['redeemed_at'])->format('Y-m-d H:i') }}
                                </li>
                            @empty
                                <li>Sin redenciones aún.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h4 class="font-semibold mb-3">🔍 Auditoría de intentos de código</h4>
                        <ul class="space-y-2 text-sm">
                            @forelse ($recentAttempts as $attempt)
                                <li class="border rounded-md p-2">
                                    <div>
                                        <span class="font-medium">{{ strtoupper($attempt['status']) }}</span>
                                        - {{ $attempt['email'] ?? 'sin email' }}
                                        - código: {{ $attempt['code_input'] ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        razón: {{ $attempt['reason'] ?? '-' }} |
                                        ip: {{ $attempt['ip_address'] ?? '-' }} |
                                        {{ \Illuminate\Support\Carbon::parse($attempt['attempted_at'])->format('Y-m-d H:i') }}
                                    </div>
                                </li>
                            @empty
                                <li>Sin intentos registrados aún.</li>
                            @endforelse
                        </ul>
                    </div>

                </section>
            @endif
        </div>
    </div>

    @if ($currentTab === 'metrics')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Chart === 'undefined') {
                    return;
                }

                const courseLabels = @json($barCourseLabels).map(label => {
                    if (typeof label !== 'string') {
                        return 'Curso';
                    }
                    return label.length > 24 ? label.slice(0, 24) + '…' : label;
                });
                const courseEnrollments = @json($barCourseEnrollments).map(value => {
                    const numeric = Number(value);
                    if (Number.isNaN(numeric)) {
                        return 0;
                    }
                    return Math.max(0, numeric);
                });
                const courseProgress = @json($barCourseProgress).map(value => {
                    const numeric = Number(value);
                    if (Number.isNaN(numeric)) {
                        return 0;
                    }
                    return Math.max(0, Math.min(100, numeric));
                });
                const leaderboardTop = @json($leaderboardTop);
                const chartTextColor = '#ffedd5';
                const chartGridColor = 'rgba(251, 146, 60, 0.18)';
                const formatCompact = (value) => {
                    const numeric = Number(value);
                    if (numeric >= 1000000) return (numeric / 1000000).toFixed(1) + 'M';
                    if (numeric >= 1000) return (numeric / 1000).toFixed(1) + 'K';
                    return String(Math.round(numeric));
                };

                const commonOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { right: 8, left: 8 }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartTextColor,
                                boxWidth: 14,
                                boxHeight: 14
                            }
                        },
                        tooltip: {
                            titleColor: '#fff7ed',
                            bodyColor: '#ffedd5',
                            backgroundColor: 'rgba(15, 10, 10, 0.92)',
                            borderColor: 'rgba(251, 146, 60, 0.5)',
                            borderWidth: 1
                        }
                    }
                };

                const coursesCtx = document.getElementById('coursesBarChart');
                if (coursesCtx) {
                    new Chart(coursesCtx, {
                        type: 'bar',
                        data: {
                            labels: courseLabels,
                            datasets: [
                                {
                                    label: 'Matrículas',
                                    data: courseEnrollments,
                                    yAxisID: 'yEnrollments',
                                    backgroundColor: 'rgba(79, 70, 229, 0.7)',
                                    borderColor: 'rgba(79, 70, 229, 1)',
                                    borderWidth: 1,
                                    maxBarThickness: 42,
                                    categoryPercentage: 0.7,
                                    barPercentage: 0.8,
                                    clip: 8
                                },
                                {
                                    label: 'Avance %',
                                    data: courseProgress,
                                    type: 'line',
                                    yAxisID: 'yProgress',
                                    fill: false,
                                    tension: 0.25,
                                    borderColor: 'rgba(14, 165, 233, 1)',
                                    backgroundColor: 'rgba(14, 165, 233, 0.2)',
                                    pointBackgroundColor: 'rgba(14, 165, 233, 1)',
                                    pointRadius: 3,
                                    borderWidth: 2,
                                    clip: 8
                                }
                            ]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                yEnrollments: {
                                    type: 'linear',
                                    position: 'left',
                                    beginAtZero: true,
                                    grid: { color: chartGridColor },
                                    ticks: {
                                        color: chartTextColor,
                                        precision: 0,
                                        maxTicksLimit: 6,
                                        callback: function (value) { return formatCompact(value); }
                                    }
                                },
                                yProgress: {
                                    type: 'linear',
                                    position: 'right',
                                    beginAtZero: true,
                                    min: 0,
                                    max: 100,
                                    grid: { drawOnChartArea: false },
                                    ticks: { color: chartTextColor }
                                },
                                x: {
                                    grid: { color: chartGridColor },
                                    ticks: {
                                        color: chartTextColor,
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 8
                                    }
                                }
                            }
                        }
                    });
                }

                const codesCtx = document.getElementById('codesPieChart');
                if (codesCtx) {
                    new Chart(codesCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Usados', 'No usados'],
                            datasets: [{
                                data: [{{ $summary['codes_used'] }}, {{ $summary['codes_unused'] }}],
                                backgroundColor: ['rgba(79, 70, 229, 0.8)', 'rgba(148, 163, 184, 0.7)'],
                                borderWidth: 1,
                                clip: 8
                            }]
                        },
                        options: commonOptions
                    });
                }

                const quizCtx = document.getElementById('quizStatusChart');
                if (quizCtx) {
                    new Chart(quizCtx, {
                        type: 'pie',
                        data: {
                            labels: ['Activos', 'Inactivos'],
                            datasets: [{
                                data: [{{ $quizActive }}, {{ $quizInactive }}],
                                backgroundColor: ['rgba(34, 197, 94, 0.75)', 'rgba(244, 63, 94, 0.7)'],
                                borderWidth: 1,
                                clip: 8
                            }]
                        },
                        options: commonOptions
                    });
                }

                const leaderboardCtx = document.getElementById('leaderboardChart');
                if (leaderboardCtx) {
                    new Chart(leaderboardCtx, {
                        type: 'bar',
                        data: {
                            labels: leaderboardTop.map(item => item.name),
                            datasets: [{
                                label: 'XP',
                                data: leaderboardTop.map(item => item.xp_total),
                                backgroundColor: 'rgba(99, 102, 241, 0.75)',
                                borderColor: 'rgba(99, 102, 241, 1)',
                                borderWidth: 1,
                                clip: 8
                            }]
                        },
                        options: {
                            ...commonOptions,
                            indexAxis: 'y',
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    grid: { color: chartGridColor },
                                    ticks: {
                                        color: chartTextColor,
                                        maxTicksLimit: 6,
                                        callback: function (value) { return formatCompact(value); }
                                    }
                                },
                                y: {
                                    grid: { color: chartGridColor },
                                    ticks: {
                                        color: chartTextColor,
                                        autoSkip: true,
                                        maxTicksLimit: 8
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
</x-app-layout>
