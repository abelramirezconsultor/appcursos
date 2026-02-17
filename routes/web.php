<?php

use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tenants/create', [TenantRegistrationController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantRegistrationController::class, 'store'])->name('tenants.store');
});

Route::post('/registro-tenant', [TenantRegistrationController::class, 'store'])->name('tenant.register');

require __DIR__.'/auth.php';
