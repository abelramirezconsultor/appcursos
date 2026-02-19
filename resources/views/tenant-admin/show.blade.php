<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tenant Admin: ') . $tenant->name . ' (' . $tenant->id . ')' }}
        </h2>
    </x-slot>

    <div class="py-10 min-h-screen bg-gradient-to-b from-indigo-50 via-sky-50 to-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-md p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-md p-4">
                URL de acceso de estudiantes:
                <a href="{{ route('tenant.student.login', ['tenant' => $tenant->alias]) }}" class="underline">
                    {{ route('tenant.student.login', ['tenant' => $tenant->alias]) }}
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-4">
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Students</p><p class="text-2xl font-semibold">{{ $summary['students_count'] }}</p></div>
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Cursos</p><p class="text-2xl font-semibold">{{ $summary['courses_count'] }}</p></div>
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Módulos / Lecciones</p><p class="text-2xl font-semibold">{{ $summary['modules_count'] }} / {{ $summary['lessons_count'] }}</p></div>
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Matrículas</p><p class="text-2xl font-semibold">{{ $summary['enrollments_count'] }}</p></div>
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Avance promedio</p><p class="text-2xl font-semibold">{{ $summary['avg_progress_percent'] }}%</p></div>
                <div class="bg-white/90 backdrop-blur border border-slate-100 shadow-sm rounded-lg p-4"><p class="text-xs text-gray-500 uppercase">Insignias otorgadas</p><p class="text-2xl font-semibold">{{ $summary['badges_awarded'] }}</p></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Crear usuario normal (student)</h3>
                    <form method="POST" action="{{ route('tenant-admin.students.store', $tenant->id) }}" class="space-y-4">
                        @csrf
                        <div><x-input-label for="student_name" :value="__('Nombre')" /><x-text-input id="student_name" class="block mt-1 w-full" type="text" name="name" required /></div>
                        <div><x-input-label for="student_email" :value="__('Email')" /><x-text-input id="student_email" class="block mt-1 w-full" type="email" name="email" required /></div>
                        <div><x-input-label for="student_password" :value="__('Contraseña inicial')" /><x-text-input id="student_password" class="block mt-1 w-full" type="password" name="password" required /></div>
                        <x-primary-button>Crear usuario normal</x-primary-button>
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Crear curso</h3>
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
                        <x-primary-button>Crear curso</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Crear módulo</h3>
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
                        <x-primary-button>Crear módulo</x-primary-button>
                    </form>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Crear lección (video corto)</h3>
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><x-input-label for="lesson_duration_seconds" :value="__('Duración seg')" /><x-text-input id="lesson_duration_seconds" class="block mt-1 w-full" type="number" min="30" max="900" name="duration_seconds" /></div>
                            <div><x-input-label for="lesson_position" :value="__('Posición')" /><x-text-input id="lesson_position" class="block mt-1 w-full" type="number" min="1" name="position" /></div>
                            <div><x-input-label for="lesson_xp_reward" :value="__('XP')" /><x-text-input id="lesson_xp_reward" class="block mt-1 w-full" type="number" min="0" name="xp_reward" value="0" /></div>
                        </div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_preview" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ms-2 text-sm text-gray-600">Vista previa</span>
                        </label>
                        <x-primary-button>Crear lección</x-primary-button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Asignar código de activación</h3>
                <form method="POST" action="{{ route('tenant-admin.codes.store', $tenant->id) }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
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
                    <div><x-input-label for="code_value" :value="__('Código (opcional)')" /><x-text-input id="code_value" class="block mt-1 w-full" type="text" name="code" /></div>
                    <div><x-input-label for="code_expires" :value="__('Expira (opcional)')" /><x-text-input id="code_expires" class="block mt-1 w-full" type="datetime-local" name="expires_at" /></div>
                    <div><x-primary-button>Asignar código</x-primary-button></div>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h4 class="font-semibold mb-3">Métricas por curso</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pr-3">Curso</th><th class="py-2 pr-3">Estado</th><th class="py-2 pr-3">Módulos</th><th class="py-2 pr-3">Lecciones</th><th class="py-2 pr-3">Matrículas</th><th class="py-2 pr-3">Avance prom.</th></tr></thead>
                        <tbody>
                            @forelse ($courseMetrics as $metric)
                                <tr class="border-b last:border-0"><td class="py-2 pr-3">{{ $metric['title'] }}</td><td class="py-2 pr-3">{{ $metric['status'] }}</td><td class="py-2 pr-3">{{ $metric['modules_count'] }}</td><td class="py-2 pr-3">{{ $metric['lessons_count'] }}</td><td class="py-2 pr-3">{{ $metric['enrollments_count'] }}</td><td class="py-2 pr-3">{{ $metric['avg_progress_percent'] }}%</td></tr>
                            @empty
                                <tr><td colspan="6" class="py-3 text-gray-500">Sin métricas aún.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h4 class="font-semibold mb-3">Usuarios normales</h4>
                    <ul class="space-y-2 text-sm">
                        @forelse ($students as $student)
                            <li>{{ $student['name'] }} - {{ $student['email'] }}</li>
                        @empty
                            <li>Sin usuarios aún.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h4 class="font-semibold mb-3">Cursos</h4>
                    <ul class="space-y-2 text-sm">
                        @forelse ($courses as $course)
                            <li>{{ $course['title'] }} ({{ $course['status'] }})</li>
                        @empty
                            <li>Sin cursos aún.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h4 class="font-semibold mb-3">Códigos recientes</h4>
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
                <h4 class="font-semibold mb-3">Redenciones recientes</h4>
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
                <h4 class="font-semibold mb-3">Leaderboard (Gamificación)</h4>
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
        </div>
    </div>
</x-app-layout>
