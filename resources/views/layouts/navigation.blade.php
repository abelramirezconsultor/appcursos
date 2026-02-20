@php
    $isGamingNav = request()->routeIs('dashboard')
        || request()->routeIs('tenant-admin.show')
        || request()->routeIs('tenants.create')
        || request()->routeIs('profile.*')
        || request()->is('dashboard')
        || request()->is('tenant-admin/*')
        || request()->is('tenants/create')
        || request()->is('profile');

    $isTenantAdminRoute = request()->routeIs('tenant-admin.show');
    $tenantRouteParam = request()->route('tenant');
    $tenantTabSection = request()->query('section', 'course');

    $tenantNavModel = null;
    $tenantNavName = null;
    if ($isTenantAdminRoute && $tenantRouteParam) {
        if ($tenantRouteParam instanceof \App\Models\Tenant) {
            $tenantNavModel = $tenantRouteParam;
            $tenantNavName = $tenantRouteParam->name;
        } else {
            $tenantIdentifier = (string) $tenantRouteParam;
            $tenantNavModel = \App\Models\Tenant::query()
                ->where('id', $tenantIdentifier)
                ->orWhere('slug', $tenantIdentifier)
                ->orWhere('alias', $tenantIdentifier)
                ->first(['id', 'name']);

            $tenantNavName = $tenantNavModel?->name;
        }
    } elseif ($isTenantAdminRoute) {
        $tenantIdentifier = (string) request()->segment(3);
        if ($tenantIdentifier !== '') {
            $tenantNavModel = \App\Models\Tenant::query()
                ->where('id', $tenantIdentifier)
                ->orWhere('slug', $tenantIdentifier)
                ->orWhere('alias', $tenantIdentifier)
                ->first(['id', 'name']);

            $tenantNavName = $tenantNavModel?->name;
        }
    }

    $quickActionCourses = [];
    $quickActionModules = [];
    if ($isTenantAdminRoute && $tenantNavModel) {
        $tenantNavModel->run(function () use (&$quickActionCourses, &$quickActionModules): void {
            $courses = \App\Models\Course::query()
                ->orderBy('title')
                ->get(['id', 'title']);

            $courseTitlesById = $courses
                ->mapWithKeys(fn ($course) => [(int) $course->id => (string) $course->title])
                ->all();

            $quickActionCourses = $courses
                ->map(fn ($course) => [
                    'id' => (int) $course->id,
                    'title' => (string) $course->title,
                ])
                ->values()
                ->all();

            $quickActionModules = \App\Models\CourseModule::query()
                ->orderBy('course_id')
                ->orderBy('position')
                ->get(['id', 'course_id', 'title'])
                ->map(fn ($module) => [
                    'id' => (int) $module->id,
                    'course_id' => (int) $module->course_id,
                    'course_title' => $courseTitlesById[(int) $module->course_id] ?? 'Curso',
                    'title' => (string) $module->title,
                ])
                ->values()
                ->all();
        });
    }

    $isSidebarLayout = auth()->check() && $isGamingNav;
    $platformDisplayName = $tenantNavName ?: 'Bravon';
    $platformDisplayNameUpper = \Illuminate\Support\Str::upper($platformDisplayName);
    $topNavContainerClass = $isSidebarLayout
        ? 'w-full px-4 sm:px-6 lg:px-8'
        : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

    $navLogoCandidates = [
        'images/bravon-logo.png',
        'images/bravon-logo.svg',
        'favicon.png',
    ];
    $navLogoAsset = 'favicon.png';
    $navLogoVersion = null;
    foreach ($navLogoCandidates as $candidate) {
        $candidatePath = public_path($candidate);
        if (is_file($candidatePath)) {
            $navLogoAsset = $candidate;
            $navLogoVersion = (string) filemtime($candidatePath);
            break;
        }
    }
    $navLogoSrc = asset($navLogoAsset) . ($navLogoVersion ? ('?v=' . $navLogoVersion) : '');
@endphp

<nav x-data="{ open: false, quickModal: null, exitModal: false, logoutModal: false }" class="{{ $isGamingNav ? 'gaming-nav bg-transparent border-b border-orange-500/30' : 'bg-white border-b border-gray-100' }}" @if($isSidebarLayout) :class="sidebarExpanded ? 'sm:pl-64' : 'sm:pl-20'" @endif>
    <div class="{{ $topNavContainerClass }}">
        <div class="flex h-16 items-center">
            <div class="flex items-center gap-3 sm:w-1/3">
                @if ($isSidebarLayout)
                    <button @click="open = ! open" class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-orange-200 hover:text-white hover:bg-orange-700/30 focus:outline-none transition">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                @endif
            </div>

            @if ($isSidebarLayout)
                <div class="hidden sm:flex sm:flex-1 justify-center">
                    <span class="text-orange-100 text-sm font-semibold tracking-wider uppercase text-center">
                        {{ $platformDisplayNameUpper }}
                    </span>
                </div>
            @endif

            <div class="hidden sm:flex sm:w-1/3 sm:justify-end sm:items-center gap-3">
                @if ($isTenantAdminRoute && $tenantRouteParam)
                    <div class="flex items-center pr-3 mr-1 border-r border-orange-500/35">
                        <div class="flex items-center gap-2" style="margin-right: clamp(48px, 7vw, 112px);">
                            <button type="button" @click="quickModal = 'course'" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-500/60 text-orange-200 bg-black/40 hover:text-white hover:bg-orange-700/25 focus:outline-none transition" title="Nueva partida (crear curso)">
                                <i class="bi bi-journal-plus"></i>
                            </button>

                            <button type="button" @click="quickModal = 'student'" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-500/60 text-orange-200 bg-black/40 hover:text-white hover:bg-orange-700/25 focus:outline-none transition" title="Crear usuario normal">
                                <i class="bi bi-people-fill"></i>
                            </button>

                            <button type="button" @click="quickModal = 'quiz'" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-500/60 text-orange-200 bg-black/40 hover:text-white hover:bg-orange-700/25 focus:outline-none transition" title="Crear quiz">
                                <i class="bi bi-ui-checks-grid"></i>
                            </button>

                            <button type="button" @click="window.location.reload()" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-500/60 text-orange-200 bg-black/40 hover:text-white hover:bg-orange-700/25 focus:outline-none transition" title="Recargar tab actual">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-orange-500/60 text-orange-200 bg-black/40 hover:text-white hover:bg-orange-700/25 focus:outline-none transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-100">
                            {{ Auth::user()->name }}
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        @if ($isTenantAdminRoute && $tenantRouteParam)
                            <x-dropdown-link :href="route('logout')" @click.prevent="logoutModal = true">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        @else
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Cerrar sesión') }}
                                </x-dropdown-link>
                            </form>
                        @endif
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>

    @if ($isSidebarLayout)
           <a href="{{ route('dashboard') }}"
              class="hidden sm:flex fixed z-50 items-center justify-center transition-all duration-200"
               :style="sidebarExpanded ? 'left: 100px; top: 12px;' : 'left: 12px; top: 12px;'"
               @if ($isTenantAdminRoute && $tenantRouteParam) @click.prevent="exitModal = true" @endif>
              <img src="{{ $navLogoSrc }}"
                 alt="Bravon"
                  style="height: 72px; width: auto; border-radius: 18px; border: 2px solid rgba(251,146,60,0.55); box-shadow: 0 0 0 1px rgba(251,146,60,0.25), 0 0 10px rgba(249,115,22,0.35), 0 0 20px rgba(239,68,68,0.22); filter: saturate(1.08) contrast(1.01); transition: box-shadow 200ms ease, border-color 200ms ease, filter 200ms ease;"
                  onmouseenter="this.style.borderColor='rgba(251,146,60,0.85)'; this.style.boxShadow='0 0 0 1px rgba(251,146,60,0.45), 0 0 16px rgba(249,115,22,0.75), 0 0 30px rgba(239,68,68,0.52), 0 0 46px rgba(220,38,38,0.30)'; this.style.filter='saturate(1.15) contrast(1.03)';"
                  onmouseleave="this.style.borderColor='rgba(251,146,60,0.55)'; this.style.boxShadow='0 0 0 1px rgba(251,146,60,0.25), 0 0 10px rgba(249,115,22,0.35), 0 0 20px rgba(239,68,68,0.22)'; this.style.filter='saturate(1.08) contrast(1.01)';"
                 class="object-contain" />
        </a>

        <aside class="hidden sm:flex sm:fixed sm:inset-y-0 sm:left-0 sm:flex-col border-r border-orange-500/30 bg-black/70 backdrop-blur-md transition-all duration-200" :class="sidebarExpanded ? 'sm:w-64' : 'sm:w-20'">
            <div class="h-16 shrink-0"></div>

            <div class="shrink-0" style="height: 8.8rem;"></div>

            <div class="p-3 pt-6 space-y-2 text-sm">
                <a href="{{ route('dashboard') }}" @if ($isTenantAdminRoute && $tenantRouteParam) @click.prevent="exitModal = true" @endif class="flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }"><i class="bi bi-controller w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span x-show="sidebarExpanded">Panel Central</span></a>

                @if ($isTenantAdminRoute && $tenantRouteParam)
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'metrics']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->query('tab', 'metrics') === 'metrics' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }"><i class="bi bi-fire w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span x-show="sidebarExpanded">Métricas</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'tracking']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->query('tab') === 'tracking' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }"><i class="bi bi-crosshair2 w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span x-show="sidebarExpanded">Tracking</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'academy', 'section' => $tenantTabSection]) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->query('tab') === 'academy' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }"><i class="bi bi-joystick w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span x-show="sidebarExpanded">My academy</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'leaderboard']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->query('tab') === 'leaderboard' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }"><i class="bi bi-award-fill w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span x-show="sidebarExpanded">Leaderboard</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'heatmap']) }}" class="g-tab-fire flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all {{ request()->query('tab') === 'heatmap' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}" :class="{ 'justify-center': !sidebarExpanded }">
                        <img src="{{ asset('images/paw.svg') }}" alt="" aria-hidden="true" class="w-7 h-7 text-center shrink-0 heatmap-paw-img heatmap-paw-default" />
                        <span x-show="sidebarExpanded">Heatmap</span>
                    </a>
                @endif
            </div>

            <div class="mt-auto p-3 border-t border-orange-500/30">
                <button type="button" @click="sidebarExpanded = !sidebarExpanded" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-md text-orange-200 hover:bg-orange-700/30 hover:text-white transition">
                    <i class="bi" :class="sidebarExpanded ? 'bi-chevron-left' : 'bi-chevron-right'" aria-hidden="true"></i>
                </button>
            </div>
        </aside>

        <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-orange-500/30 bg-black/80">
            <div class="p-3 space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" @if ($isTenantAdminRoute && $tenantRouteParam) @click.prevent="exitModal = true" @endif class="flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->routeIs('dashboard') ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}"><i class="bi bi-controller w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span>Panel Central</span></a>

                @if ($isTenantAdminRoute && $tenantRouteParam)
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'metrics']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->query('tab', 'metrics') === 'metrics' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}"><i class="bi bi-fire w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span>Métricas</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'tracking']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->query('tab') === 'tracking' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}"><i class="bi bi-crosshair2 w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span>Tracking</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'academy', 'section' => $tenantTabSection]) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->query('tab') === 'academy' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}"><i class="bi bi-joystick w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span>My academy</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'leaderboard']) }}" class="flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->query('tab') === 'leaderboard' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}"><i class="bi bi-award-fill w-7 text-center shrink-0" style="font-size: 1.25rem;" aria-hidden="true"></i><span>Leaderboard</span></a>
                    <a href="{{ route('tenant-admin.show', ['tenant' => $tenantRouteParam, 'tab' => 'heatmap']) }}" class="g-tab-fire flex items-center gap-2 px-3 py-2 rounded-md font-medium {{ request()->query('tab') === 'heatmap' ? 'bg-orange-600/80 text-white' : 'text-orange-200 hover:bg-orange-700/30 hover:text-white' }}">
                        <img src="{{ asset('images/paw.svg') }}" alt="" aria-hidden="true" class="w-7 h-7 text-center shrink-0 heatmap-paw-img heatmap-paw-default" />
                        <span>Heatmap</span>
                    </a>
                @endif

                <div class="pt-2 border-t border-orange-500/30 text-orange-200 text-xs">
                    {{ Auth::user()->name }}
                </div>
                <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md font-medium text-orange-200 hover:bg-orange-700/30 hover:text-white">Perfil</a>
                @if ($isTenantAdminRoute && $tenantRouteParam)
                    <button type="button" @click="logoutModal = true" class="w-full text-left px-3 py-2 rounded-md font-medium text-orange-200 hover:bg-orange-700/30 hover:text-white">Cerrar sesión</button>
                @else
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md font-medium text-orange-200 hover:bg-orange-700/30 hover:text-white">Cerrar sesión</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($isTenantAdminRoute && $tenantRouteParam)
            <div x-show="exitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="exitModal = false" @keydown.escape.window="exitModal = false">
                <div class="rounded-lg border border-orange-500/40 bg-slate-950/95 p-4 shadow-2xl" style="width:min(92vw, 22rem);">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-base font-semibold text-orange-100">Salir de plataforma</h3>
                        <button type="button" @click="exitModal = false" class="text-orange-200 hover:text-white">✕</button>
                    </div>
                    <p class="text-sm text-orange-100/90 mb-4">
                        Estás a punto de salir de: <span class="font-semibold text-orange-300">{{ \Illuminate\Support\Str::upper($platformDisplayName) }}</span>.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="exitModal = false" class="g-action-btn g-action-confirm"><i class="bi bi-arrow-left-circle-fill" aria-hidden="true"></i>Atrás</button>
                        <a href="{{ route('dashboard') }}" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Salir al panel</a>
                    </div>
                </div>
            </div>
        @endif

        @if ($isTenantAdminRoute && $tenantRouteParam)
            <div x-show="logoutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="logoutModal = false" @keydown.escape.window="logoutModal = false">
                <div class="rounded-lg border border-orange-500/40 bg-slate-950/95 p-4 shadow-2xl" style="width:min(92vw, 22rem);">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-base font-semibold text-orange-100">Cerrar sesión</h3>
                        <button type="button" @click="logoutModal = false" class="text-orange-200 hover:text-white">✕</button>
                    </div>
                    <p class="text-sm text-orange-100/90 mb-4">
                        Estás a punto de cerrar sesión y salir de: <span class="font-semibold text-orange-300">{{ \Illuminate\Support\Str::upper($platformDisplayName) }}</span>.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="logoutModal = false" class="g-action-btn g-action-confirm"><i class="bi bi-arrow-left-circle-fill" aria-hidden="true"></i>Atrás</button>
                        <button type="button" @click="$refs.logoutForm.submit()" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Cerrar sesión</button>
                    </div>
                </div>
            </div>
        @endif

        <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>

        @if ($isTenantAdminRoute && $tenantRouteParam)
            <div x-show="quickModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="quickModal = null" @keydown.escape.window="quickModal = null">
                <div x-show="quickModal === 'course'" class="w-full max-w-xl rounded-lg border border-orange-500/40 bg-slate-950/95 p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-orange-100">Nueva partida · Crear curso</h3>
                        <button type="button" @click="quickModal = null" class="text-orange-200 hover:text-white">✕</button>
                    </div>

                    <form method="POST" action="{{ route('tenant-admin.courses.store', $tenantRouteParam) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="quick_course_title" :value="__('Título')" />
                            <x-text-input id="quick_course_title" class="block mt-1 w-full" type="text" name="title" required />
                        </div>
                        <div>
                            <x-input-label for="quick_course_description" :value="__('Descripción')" />
                            <textarea id="quick_course_description" name="description" class="border-gray-300 rounded-md shadow-sm block mt-1 w-full"></textarea>
                        </div>
                        <div>
                            <x-input-label for="quick_course_status" :value="__('Estado')" />
                            <select id="quick_course_status" name="status" class="border-gray-300 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="quickModal = null" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Cancelar</button>
                            <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear curso</button>
                        </div>
                    </form>
                </div>

                <div x-show="quickModal === 'student'" class="w-full max-w-xl rounded-lg border border-orange-500/40 bg-slate-950/95 p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-orange-100">Jugador nuevo · Crear usuario normal</h3>
                        <button type="button" @click="quickModal = null" class="text-orange-200 hover:text-white">✕</button>
                    </div>

                    <form method="POST" action="{{ route('tenant-admin.students.store', $tenantRouteParam) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="quick_student_name" :value="__('Nombre')" />
                            <x-text-input id="quick_student_name" class="block mt-1 w-full" type="text" name="name" required />
                        </div>
                        <div>
                            <x-input-label for="quick_student_email" :value="__('Email')" />
                            <x-text-input id="quick_student_email" class="block mt-1 w-full" type="email" name="email" required />
                        </div>
                        <div>
                            <x-input-label for="quick_student_password" :value="__('Contraseña inicial')" />
                            <x-text-input id="quick_student_password" class="block mt-1 w-full" type="password" name="password" required />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="quickModal = null" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Cancelar</button>
                            <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear usuario</button>
                        </div>
                    </form>
                </div>

                <div x-show="quickModal === 'quiz'" class="w-full max-w-xl rounded-lg border border-orange-500/40 bg-slate-950/95 p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-orange-100">Insignia · Crear quiz</h3>
                        <button type="button" @click="quickModal = null" class="text-orange-200 hover:text-white">✕</button>
                    </div>

                    <form method="POST" action="{{ route('tenant-admin.quizzes.store', $tenantRouteParam) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="quick_quiz_course_id" :value="__('Curso')" />
                            <select id="quick_quiz_course_id" name="course_id" class="border-gray-300 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="">Seleccione</option>
                                @foreach ($quickActionCourses as $course)
                                    <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="quick_quiz_module_id" :value="__('Módulo (opcional)')" />
                            <select id="quick_quiz_module_id" name="module_id" class="border-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">Sin módulo específico</option>
                                @foreach ($quickActionModules as $module)
                                    <option value="{{ $module['id'] }}">{{ $module['course_title'] }} / {{ $module['title'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="quick_quiz_title" :value="__('Título del quiz')" />
                            <x-text-input id="quick_quiz_title" class="block mt-1 w-full" type="text" name="title" required />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <x-input-label for="quick_quiz_minimum_score" :value="__('Nota mínima %')" />
                                <x-text-input id="quick_quiz_minimum_score" class="block mt-1 w-full" type="number" min="0" max="100" name="minimum_score" value="70" required />
                            </div>
                            <div>
                                <x-input-label for="quick_quiz_max_attempts" :value="__('Intentos máximos')" />
                                <x-text-input id="quick_quiz_max_attempts" class="block mt-1 w-full" type="number" min="1" max="20" name="max_attempts" />
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ms-2 text-sm text-orange-200">Activo</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="quickModal = null" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Cancelar</button>
                            <button type="submit" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Crear quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif
</nav>
