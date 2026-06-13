<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\CertificateRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')
        ->name('dashboard.student');

    Route::get('/administrator/dashboard', [DashboardController::class, 'administrator'])
        ->middleware('role:administrator')
        ->name('dashboard.administrator');

    Route::get('/scholarship-agency/dashboard', [DashboardController::class, 'scholarshipAgency'])
        ->middleware('role:scholarship_agency')
        ->name('dashboard.scholarship-agency');

    Route::get('/coordinator/dashboard', [DashboardController::class, 'coordinator'])
        ->middleware('role:coordinator')
        ->name('dashboard.coordinator');

    Route::get('/scholarship-chairman/dashboard', [DashboardController::class, 'scholarshipChairman'])
        ->middleware('role:scholarship_chairman')
        ->name('dashboard.scholarship-chairman');

    Route::middleware('role:student')
        ->prefix('student/certificate-requests')
        ->name('student.certificate-requests.')
        ->group(function () {
            Route::get('/', [CertificateRequestController::class, 'index'])->name('index');
            Route::get('/create', [CertificateRequestController::class, 'create'])->name('create');
            Route::post('/', [CertificateRequestController::class, 'store'])->name('store');
            Route::get('/{certificateRequest}', [CertificateRequestController::class, 'show'])->name('show');
            Route::get('/{certificateRequest}/certificate', [CertificateRequestController::class, 'downloadCertificate'])
                ->name('certificate.download');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
