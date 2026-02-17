<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\RegisterTenantService;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'courseAreas' => $this->courseAreas(),
            'experienceRanges' => $this->experienceRanges(),
            'teamSizeRanges' => $this->teamSizeRanges(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, RegisterTenantService $registerTenantService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', 'unique:tenants,alias'],
            'course_area' => ['required', 'string', Rule::in($this->courseAreas())],
            'years_experience' => ['required', 'string', Rule::in($this->experienceRanges())],
            'team_size_range' => ['nullable', 'string', Rule::in($this->teamSizeRanges())],
            'expected_students_6m' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'planned_courses_year_1' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        try {
            $registerTenantService->register($validated);
        } catch (\Throwable $exception) {
            $user->delete();

            throw $exception;
        }

        event(new Registered($user));

        $authenticated = Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        if (! $authenticated) {
            Auth::login($user);
        }

        $request->session()->regenerate();

        return redirect(route('dashboard', absolute: false));
    }

    private function courseAreas(): array
    {
        return [
            'Medicina',
            'Derecho',
            'Contabilidad',
            'Ingeniería',
            'Tecnología',
            'Marketing',
            'Finanzas',
            'Educación',
            'Recursos Humanos',
            'Otra',
        ];
    }

    private function experienceRanges(): array
    {
        return [
            '0-2',
            '3-5',
            '6-10',
            '11-15',
            '16-20',
            '21+',
        ];
    }

    private function teamSizeRanges(): array
    {
        return [
            'Solo',
            '2-5',
            '6-10',
            '11-25',
            '26+',
        ];
    }
}
