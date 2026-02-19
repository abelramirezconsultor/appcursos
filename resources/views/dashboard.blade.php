<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Central') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-md p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4">{{ __('Has iniciado sesión en la aplicación central.') }}</p>

                    <a href="{{ route('tenants.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        {{ __('Registrar nueva plataforma de cursos') }}
                    </a>

                    @php
                        $ownedTenants = \App\Models\Tenant::query()
                            ->where('owner_email', auth()->user()->email)
                            ->orderByDesc('created_at')
                            ->get(['id', 'name', 'slug']);
                    @endphp

                    <div class="mt-6">
                        <h3 class="font-semibold mb-2">Mis plataformas</h3>
                        <ul class="space-y-2">
                            @forelse ($ownedTenants as $tenant)
                                <li>
                                    <a href="{{ route('tenant-admin.show', $tenant->id) }}" class="text-indigo-700 underline">
                                        {{ $tenant->name }} ({{ $tenant->id }})
                                    </a>
                                </li>
                            @empty
                                <li>No tienes plataformas todavía.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
