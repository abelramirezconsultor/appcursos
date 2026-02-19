<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Models\ActivationCodeRedemption;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantActivationController extends Controller
{
    public function create(): View
    {
        return view('tenant.activate-code', [
            'tenantRouteKey' => $this->tenantRouteKey(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas para este tenant.',
            ])->withInput($request->except('password'));
        }

        $codeValue = strtoupper(trim($validated['code']));

        /** @var ActivationCode|null $activationCode */
        $activationCode = ActivationCode::query()
            ->where('code', $codeValue)
            ->where('is_active', true)
            ->first();

        if (! $activationCode) {
            return back()->withErrors([
                'code' => 'Código inválido o inactivo.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->assigned_user_id && (int) $activationCode->assigned_user_id !== (int) $user->id) {
            return back()->withErrors([
                'code' => 'Este código no está asignado a tu usuario.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->expires_at && now()->greaterThan($activationCode->expires_at)) {
            return back()->withErrors([
                'code' => 'El código ya expiró.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->uses_count >= $activationCode->max_uses) {
            return back()->withErrors([
                'code' => 'El código alcanzó su límite de uso.',
            ])->withInput($request->except('password'));
        }

        DB::transaction(function () use ($activationCode, $user, $request): void {
            $enrollment = CourseEnrollment::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $activationCode->course_id,
                ],
                [
                    'activation_code_id' => $activationCode->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                ]
            );

            $redemption = ActivationCodeRedemption::query()->firstOrCreate(
                [
                    'activation_code_id' => $activationCode->id,
                    'user_id' => $user->id,
                ],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'redeemed_at' => now(),
                ]
            );

            if ($redemption->wasRecentlyCreated) {
                $activationCode->increment('uses_count');

                if (($activationCode->uses_count + 1) >= $activationCode->max_uses) {
                    $activationCode->update([
                        'is_active' => false,
                    ]);
                }
            }

            CourseProgress::query()->firstOrCreate(
                [
                    'enrollment_id' => $enrollment->id,
                ],
                [
                    'progress_percent' => 0,
                    'last_viewed_at' => null,
                    'completed_at' => null,
                ]
            );
        });

        $request->session()->regenerate();
        $request->session()->put('tenant_student_user_id', (int) $user->id);
        $request->session()->put('tenant_student_tenant_id', $this->tenantId());

        return redirect()->route('tenant.student.courses.index', [
            'tenant' => $this->tenantRouteKey(),
        ])->with('success', 'Código activado correctamente. Ya tienes acceso al curso asignado.');
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
