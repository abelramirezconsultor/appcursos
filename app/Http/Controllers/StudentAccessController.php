<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccessController extends Controller
{
    public function create(): View
    {
        $tenants = Tenant::query()
            ->whereNotNull('alias')
            ->orderBy('alias')
            ->get(['id', 'name', 'alias']);

        return view('student-access.login', [
            'tenants' => $tenants,
        ]);
    }

    public function redirectToTenantLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alias' => ['required', 'string', 'max:50'],
        ]);

        $tenant = Tenant::query()
            ->where('alias', $validated['alias'])
            ->first();

        if (! $tenant) {
            return back()->withErrors([
                'alias' => 'La plataforma seleccionada ya no está disponible.',
            ]);
        }

        return redirect()->route('tenant.student.login', [
            'tenant' => $tenant->alias,
        ]);
    }
}
