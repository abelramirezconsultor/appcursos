<x-guest-layout>
    <h1 class="text-xl font-semibold mb-2">Ingreso de estudiantes</h1>
    <p class="text-sm text-gray-600 mb-6">Selecciona tu plataforma para ir al login del tenant.</p>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-800">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('student.access.redirect') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="alias" :value="__('Plataforma')" />
            <select id="alias" name="alias" required class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                <option value="">Seleccione una plataforma</option>
                @foreach ($tenants as $tenant)
                    <option value="{{ $tenant->alias }}" @selected(old('alias') === $tenant->alias)>
                        {{ $tenant->alias }} - {{ $tenant->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <x-primary-button class="w-full justify-center">
            Ir al login de estudiante
        </x-primary-button>
    </form>
</x-guest-layout>
