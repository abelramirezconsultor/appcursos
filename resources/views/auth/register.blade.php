<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="business_name" :value="__('Nombre de la plataforma')" />
            <x-text-input id="business_name" class="block mt-1 w-full" type="text" name="business_name" :value="old('business_name')" required autofocus />
            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="alias" :value="__('Alias (para subdominio futuro)')" />
            <x-text-input id="alias" class="block mt-1 w-full" type="text" name="alias" :value="old('alias')" placeholder="appcursos" required />
            <p class="mt-1 text-sm text-gray-500">Se recomienda un alias corto; este valor se usa para subdominio en producción.</p>
            <x-input-error :messages="$errors->get('alias')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="course_area" :value="__('Área principal de cursos')" />
            <select id="course_area" name="course_area" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                <option value="">Seleccione un área</option>
                @foreach ($courseAreas as $area)
                    <option value="{{ $area }}" @selected(old('course_area') === $area)>{{ $area }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('course_area')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="years_experience" :value="__('Antigüedad profesional')" />
            <select id="years_experience" name="years_experience" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                <option value="">Seleccione rango</option>
                @foreach ($experienceRanges as $range)
                    <option value="{{ $range }}" @selected(old('years_experience') === $range)>{{ $range }} años</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('years_experience')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="team_size_range" :value="__('Tamaño del equipo')" />
            <select id="team_size_range" name="team_size_range" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                <option value="">Seleccione rango</option>
                @foreach ($teamSizeRanges as $range)
                    <option value="{{ $range }}" @selected(old('team_size_range') === $range)>{{ $range }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('team_size_range')" class="mt-2" />
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="expected_students_6m" :value="__('Meta alumnos (6 meses)')" />
                <x-text-input id="expected_students_6m" class="block mt-1 w-full" type="number" min="0" name="expected_students_6m" :value="old('expected_students_6m')" />
                <x-input-error :messages="$errors->get('expected_students_6m')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="planned_courses_year_1" :value="__('Cursos plan año 1')" />
                <x-text-input id="planned_courses_year_1" class="block mt-1 w-full" type="number" min="0" name="planned_courses_year_1" :value="old('planned_courses_year_1')" />
                <x-input-error :messages="$errors->get('planned_courses_year_1')" class="mt-2" />
            </div>
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
