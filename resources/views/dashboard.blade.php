<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight tracking-wide">
            {{ __('Dashboard Central') }}
        </h2>
    </x-slot>

    <div class="py-12 gaming-page" x-data="{ showTenantModal: false, selectedTenantName: '', selectedTenantUrl: '' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 bg-red-950/50 border border-red-500/50 text-red-200 rounded-md p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden shadow-2xl sm:rounded-lg border border-orange-500/30 bg-slate-950/80 backdrop-blur">
                <div class="p-6 text-slate-100">
                    <p class="mb-4">{{ __('Has iniciado sesión en la aplicación central.') }}</p>

                    <a href="{{ route('tenants.create') }}" class="g-action-btn g-action-accept">
                        <i class="bi bi-triangle-fill" aria-hidden="true"></i>
                        {{ __('Registrar nueva plataforma de cursos') }}
                    </a>

                    @php
                        $ownedTenants = \App\Models\Tenant::query()
                            ->where('owner_email', auth()->user()->email)
                            ->orderByDesc('created_at')
                            ->get(['id', 'name', 'slug']);
                    @endphp

                    <div class="mt-6">
                        <h3 class="font-semibold mb-2 text-orange-300">Mis plataformas</h3>
                        <ul class="space-y-3">
                            @forelse ($ownedTenants as $tenant)
                                <li>
                                    <div class="rounded-lg border border-orange-500/35 bg-black/35 p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-orange-100 tracking-wide">
                                                {{ \Illuminate\Support\Str::upper((string) $tenant->name) }}
                                            </p>
                                            <p class="text-xs text-orange-300/90 mt-1">
                                                Referencia para soporte técnico: {{ $tenant->id }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="g-action-btn g-action-accept"
                                            @click="selectedTenantName = '{{ \Illuminate\Support\Str::upper((string) $tenant->name) }}'; selectedTenantUrl = '{{ route('tenant-admin.show', $tenant->id) }}'; showTenantModal = true"
                                        >
                                            <i class="bi bi-triangle-fill" aria-hidden="true"></i>
                                            Administrar
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li>No tienes plataformas todavía.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="showTenantModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="showTenantModal = false" @keydown.escape.window="showTenantModal = false">
            <div class="rounded-lg border border-orange-500/40 bg-slate-950/95 p-4 shadow-2xl" style="width:min(92vw, 22rem);">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold text-orange-100">Confirmar acceso</h3>
                    <button type="button" @click="showTenantModal = false" class="text-orange-200 hover:text-white">✕</button>
                </div>
                <p class="text-sm text-orange-200 mb-4">
                    Ingresarás a la plataforma <span class="font-semibold" x-text="selectedTenantName"></span>.
                </p>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showTenantModal = false" class="g-action-btn g-action-danger"><i class="bi bi-circle-fill" aria-hidden="true"></i>Cancelar</button>
                    <button type="button" @click="window.location.href = selectedTenantUrl" class="g-action-btn g-action-accept"><i class="bi bi-triangle-fill" aria-hidden="true"></i>Continuar</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
