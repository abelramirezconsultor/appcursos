<?php

declare(strict_types=1);

use App\Http\Controllers\TenantActivationController;
use App\Http\Controllers\TenantStudentAuthController;
use App\Http\Controllers\TenantStudentController;
use App\Http\Middleware\ResolveTenantAliasInPath;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

$identificationMode = (string) env('TENANCY_IDENTIFICATION', 'auto');

if ($identificationMode === 'auto') {
    $identificationMode = app()->environment('production') ? 'subdomain' : 'path';
}

if ($identificationMode === 'subdomain') {
    $baseDomain = (string) env('APP_BASE_DOMAIN', 'appcursos.test');

    Route::middleware([
        'web',
        InitializeTenancyBySubdomain::class,
    ])->domain('{tenant}.' . $baseDomain)->group(function () {
        Route::get('/activar-codigo', [TenantActivationController::class, 'create'])->name('tenant.activation.create');
        Route::post('/activar-codigo', [TenantActivationController::class, 'store'])->name('tenant.activation.store');

        Route::get('/login', [TenantStudentAuthController::class, 'create'])->name('tenant.student.login');
        Route::post('/login', [TenantStudentAuthController::class, 'store'])->name('tenant.student.login.store');
        Route::post('/logout', [TenantStudentAuthController::class, 'destroy'])->name('tenant.student.logout');

        Route::get('/mis-cursos', [TenantStudentController::class, 'index'])->name('tenant.student.courses.index');
        Route::get('/mis-cursos/{enrollment}', [TenantStudentController::class, 'show'])->name('tenant.student.courses.show');
        Route::post('/mis-cursos/{enrollment}/lecciones/{lesson}/completar', [TenantStudentController::class, 'completeLesson'])->name('tenant.student.lessons.complete');

        Route::get('/dashboard', function () {
            return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
        });
    });
} else {
    Route::middleware([
        'web',
        ResolveTenantAliasInPath::class,
        InitializeTenancyByPath::class,
    ])->prefix('/t/{tenant}')->where(['tenant' => '[A-Za-z0-9_-]+'])->group(function () {
        Route::get('/activar-codigo', [TenantActivationController::class, 'create'])->name('tenant.activation.create');
        Route::post('/activar-codigo', [TenantActivationController::class, 'store'])->name('tenant.activation.store');

        Route::get('/login', [TenantStudentAuthController::class, 'create'])->name('tenant.student.login');
        Route::post('/login', [TenantStudentAuthController::class, 'store'])->name('tenant.student.login.store');
        Route::post('/logout', [TenantStudentAuthController::class, 'destroy'])->name('tenant.student.logout');

        Route::get('/mis-cursos', [TenantStudentController::class, 'index'])->name('tenant.student.courses.index');
        Route::get('/mis-cursos/{enrollment}', [TenantStudentController::class, 'show'])->name('tenant.student.courses.show');
        Route::post('/mis-cursos/{enrollment}/lecciones/{lesson}/completar', [TenantStudentController::class, 'completeLesson'])->name('tenant.student.lessons.complete');

        Route::get('/dashboard', function () {
            return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
        });
    });
}
