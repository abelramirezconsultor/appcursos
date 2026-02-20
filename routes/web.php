<?php

use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\TenantAdminController;
use App\Http\Controllers\StudentAccessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/student-login', [StudentAccessController::class, 'create'])->name('student.access.login');
Route::post('/student-login', [StudentAccessController::class, 'redirectToTenantLogin'])->name('student.access.redirect');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tenants/create', [TenantRegistrationController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantRegistrationController::class, 'store'])->name('tenants.store');

    Route::get('/tenant-admin/{tenant}', [TenantAdminController::class, 'show'])->name('tenant-admin.show');
    Route::get('/tenant-admin/{tenant}/reports/export-csv', [TenantAdminController::class, 'exportReportsCsv'])->name('tenant-admin.reports.export-csv');
    Route::post('/tenant-admin/{tenant}/students', [TenantAdminController::class, 'storeStudent'])->name('tenant-admin.students.store');
    Route::post('/tenant-admin/{tenant}/courses', [TenantAdminController::class, 'storeCourse'])->name('tenant-admin.courses.store');
    Route::post('/tenant-admin/{tenant}/courses/{course}/toggle-status', [TenantAdminController::class, 'toggleCourseStatus'])->name('tenant-admin.courses.toggle-status');
    Route::delete('/tenant-admin/{tenant}/courses/{course}', [TenantAdminController::class, 'destroyCourse'])->name('tenant-admin.courses.destroy');
    Route::post('/tenant-admin/{tenant}/modules', [TenantAdminController::class, 'storeModule'])->name('tenant-admin.modules.store');
    Route::post('/tenant-admin/{tenant}/modules/{module}/prerequisite', [TenantAdminController::class, 'updateModulePrerequisite'])->name('tenant-admin.modules.prerequisite.update');
    Route::post('/tenant-admin/{tenant}/lessons', [TenantAdminController::class, 'storeLesson'])->name('tenant-admin.lessons.store');
    Route::post('/tenant-admin/{tenant}/quizzes', [TenantAdminController::class, 'storeQuiz'])->name('tenant-admin.quizzes.store');
    Route::post('/tenant-admin/{tenant}/quiz-questions', [TenantAdminController::class, 'storeQuizQuestion'])->name('tenant-admin.quiz-questions.store');
    Route::post('/tenant-admin/{tenant}/codes', [TenantAdminController::class, 'storeActivationCode'])->name('tenant-admin.codes.store');
    Route::post('/tenant-admin/{tenant}/codes/{code}/toggle', [TenantAdminController::class, 'toggleActivationCode'])->name('tenant-admin.codes.toggle');
});

Route::post('/registro-tenant', [TenantRegistrationController::class, 'store'])->name('tenant.register');

require __DIR__.'/auth.php';
