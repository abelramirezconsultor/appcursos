<?php

namespace App\Http\Controllers;

use App\Services\Tenancy\RegisterTenantService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function create(): View
    {
        return view('tenants.create', [
            'courseAreas' => $this->courseAreas(),
            'experienceRanges' => $this->experienceRanges(),
            'teamSizeRanges' => $this->teamSizeRanges(),
        ]);
    }

    public function store(Request $request, RegisterTenantService $registerTenantService): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $isAuthenticated = $request->user() !== null;

        $validated = $request->validate([
            'name' => [$isAuthenticated ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => [$isAuthenticated ? 'nullable' : 'required', 'string', 'email', 'max:255'],
            'password' => [$isAuthenticated ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'business_name' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', 'unique:tenants,alias'],
            'course_area' => ['required', 'string', Rule::in($this->courseAreas())],
            'years_experience' => ['required', 'string', Rule::in($this->experienceRanges())],
            'team_size_range' => ['nullable', 'string', Rule::in($this->teamSizeRanges())],
            'expected_students_6m' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'planned_courses_year_1' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        if ($isAuthenticated) {
            $validated['name'] = $request->user()->name;
            $validated['email'] = $request->user()->email;
            $validated['owner_password_hash'] = $request->user()->password;
        }

        $tenant = $registerTenantService->register($validated);

        $tenantAccessUrl = rtrim((string) config('app.url'), '/') . '/t/' . $tenant->id . '/dashboard';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tenant creado correctamente.',
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'tenant_domain' => $tenant->domains()->first()?->domain,
                'tenant_access_url' => $tenantAccessUrl,
            ], 201);
        }

        return redirect()
            ->route('tenants.create')
            ->with('success', 'Tenant creado correctamente: ' . $tenant->name)
            ->with('tenant_id', $tenant->id)
            ->with('tenant_access_url', $tenantAccessUrl);
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
