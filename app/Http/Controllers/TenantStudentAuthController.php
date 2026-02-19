<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantStudentAuthController extends Controller
{
    public function create(Request $request): View
    {
        return view('tenant.login', [
            'prefillEmail' => (string) $request->query('email', ''),
            'tenantRouteKey' => $this->tenantRouteKey(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $student = User::query()->where('email', $validated['email'])->first();

        if (! $student || ! Hash::check($validated['password'], $student->password) || ! $student->hasRole('student')) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas para este tenant.',
            ])->withInput($request->except('password'));
        }

        $request->session()->regenerate();
        $request->session()->put('tenant_student_user_id', (int) $student->id);
        $request->session()->put('tenant_student_tenant_id', $this->tenantId());

        return redirect()->route('tenant.student.courses.index', [
            'tenant' => $this->tenantRouteKey(),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenant = $this->tenantRouteKey();

        $request->session()->forget([
            'tenant_student_user_id',
            'tenant_student_tenant_id',
        ]);

        $request->session()->regenerateToken();

        return redirect()->route('tenant.student.login', [
            'tenant' => $tenant,
        ]);
    }

    private function tenantId(): string
    {
        return (string) tenant('id');
    }

    private function tenantRouteKey(): string
    {
        return (string) (tenant('alias') ?: tenant('id'));
    }
}
