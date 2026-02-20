<?php

namespace App\Http\Controllers;

use App\Models\ActivationCode;
use App\Models\ActivationCodeAttempt;
use App\Models\ActivationCodeRedemption;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantActivationController extends Controller
{
    public function create(): View
    {
        return view('tenant.activate-code', [
            'tenantRouteKey' => $this->tenantRouteKey(),
            'tenantSuspended' => $this->isTenantSuspended(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isTenantSuspended()) {
            return back()->withErrors([
                'code' => 'El tenant está suspendido. Contacta al administrador.',
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        $codeValue = strtoupper(trim($validated['code']));

        $rateLimit = $this->resolveRateLimitBlock($validated['email'], $codeValue, (string) $request->ip());
        if ($rateLimit['blocked']) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: (string) $rateLimit['reason']
            );

            return back()->withErrors([
                'code' => (string) $rateLimit['message'],
            ])->withInput($request->except('password'));
        }

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: 'invalid_credentials',
                userId: $user?->id
            );

            return back()->withErrors([
                'email' => 'Credenciales inválidas para este tenant.',
            ])->withInput($request->except('password'));
        }

        /** @var ActivationCode|null $activationCode */
        $activationCode = ActivationCode::query()
            ->where('code', $codeValue)
            ->where('is_active', true)
            ->first();

        if (! $activationCode) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: 'invalid_or_inactive_code',
                userId: $user->id
            );

            return back()->withErrors([
                'code' => 'Código inválido o inactivo.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->assigned_user_id && (int) $activationCode->assigned_user_id !== (int) $user->id) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: 'code_not_assigned_to_user',
                userId: $user->id,
                activationCodeId: $activationCode->id
            );

            return back()->withErrors([
                'code' => 'Este código no está asignado a tu usuario.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->expires_at && now()->greaterThan($activationCode->expires_at)) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: 'expired_code',
                userId: $user->id,
                activationCodeId: $activationCode->id
            );

            return back()->withErrors([
                'code' => 'El código ya expiró.',
            ])->withInput($request->except('password'));
        }

        if ($activationCode->uses_count >= $activationCode->max_uses) {
            $this->logAttempt(
                email: $validated['email'],
                codeInput: $codeValue,
                request: $request,
                status: 'failed',
                reason: 'uses_limit_reached',
                userId: $user->id,
                activationCodeId: $activationCode->id
            );

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

        $this->logAttempt(
            email: $validated['email'],
            codeInput: $codeValue,
            request: $request,
            status: 'success',
            reason: 'redeemed',
            userId: $user->id,
            activationCodeId: $activationCode->id
        );

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

    private function isTenantSuspended(): bool
    {
        return (string) (tenant('status') ?? 'active') === 'suspended';
    }

    private function resolveRateLimitBlock(string $email, string $codeInput, string $ipAddress): array
    {
        $now = now();

        $dailyStart = $now->copy()->subDay();
        $dailyFailures = ActivationCodeAttempt::query()
            ->where('status', 'failed')
            ->where('attempted_at', '>=', $dailyStart)
            ->where(function ($query) use ($email, $codeInput, $ipAddress) {
                $query->where('email', $email)
                    ->orWhere('code_input', $codeInput)
                    ->orWhere('ip_address', $ipAddress);
            });

        $dailyFailuresCount = (clone $dailyFailures)->count();
        if ($dailyFailuresCount >= 20) {
            $latestAttemptAt = (clone $dailyFailures)->max('attempted_at');
            return $this->buildRateLimitResponse($latestAttemptAt, 1440, 'rate_limited_daily');
        }

        $mediumStart = $now->copy()->subMinutes(15);
        $mediumFailures = ActivationCodeAttempt::query()
            ->where('status', 'failed')
            ->where('attempted_at', '>=', $mediumStart)
            ->where(function ($query) use ($email, $codeInput, $ipAddress) {
                $query->where('email', $email)
                    ->orWhere('code_input', $codeInput)
                    ->orWhere('ip_address', $ipAddress);
            });

        $mediumFailuresCount = (clone $mediumFailures)->count();
        if ($mediumFailuresCount >= 5) {
            $latestAttemptAt = (clone $mediumFailures)->max('attempted_at');
            return $this->buildRateLimitResponse($latestAttemptAt, 15, 'rate_limited_medium');
        }

        $burstStart = $now->copy()->subMinutes(5);
        $burstFailures = ActivationCodeAttempt::query()
            ->where('status', 'failed')
            ->where('attempted_at', '>=', $burstStart)
            ->where('ip_address', $ipAddress);

        $burstFailuresCount = (clone $burstFailures)->count();
        if ($burstFailuresCount >= 8) {
            $latestAttemptAt = (clone $burstFailures)->max('attempted_at');
            return $this->buildRateLimitResponse($latestAttemptAt, 10, 'rate_limited_burst');
        }

        return [
            'blocked' => false,
            'reason' => null,
            'message' => null,
        ];
    }

    private function buildRateLimitResponse(mixed $latestAttemptAt, int $blockMinutes, string $reason): array
    {
        $latest = $latestAttemptAt ? Carbon::parse((string) $latestAttemptAt) : now();
        $blockUntil = $latest->copy()->addMinutes($blockMinutes);

        if (now()->greaterThanOrEqualTo($blockUntil)) {
            return [
                'blocked' => false,
                'reason' => null,
                'message' => null,
            ];
        }

        $remainingMinutes = max(1, (int) ceil(now()->diffInSeconds($blockUntil) / 60));

        return [
            'blocked' => true,
            'reason' => $reason,
            'message' => 'Demasiados intentos fallidos. Intenta nuevamente en ' . $remainingMinutes . ' minuto(s).',
        ];
    }

    private function logAttempt(
        string $email,
        string $codeInput,
        Request $request,
        string $status,
        string $reason,
        ?int $userId = null,
        ?int $activationCodeId = null,
    ): void {
        ActivationCodeAttempt::query()->create([
            'activation_code_id' => $activationCodeId,
            'user_id' => $userId,
            'email' => $email,
            'code_input' => $codeInput,
            'status' => $status,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'attempted_at' => now(),
        ]);
    }
}
