<?php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\Monitoring\MonitoringDashboardController;
use App\Http\Controllers\Admin\Monitoring\ScholarRecordMonitoringController;
use App\Http\Controllers\Admin\Monitoring\ScholarshipProgramController;
use App\Http\Controllers\Admin\Monitoring\StudentMonitoringController;
use App\Http\Controllers\Admin\Monitoring\TransactionMonitoringController;
use App\Http\Controllers\Admin\OfficialReceiptVerificationController;
use App\Http\Controllers\Admin\Reports\ReportController;
use App\Http\Controllers\Agency\MasterlistController;
use App\Http\Controllers\Chairman\MasterlistApprovalController;
use App\Http\Controllers\Coordinator\MasterlistValidationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Evaluator\ScholarshipRenewalEvaluationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\CertificateRequestController;
use App\Http\Controllers\Student\ScholarshipRenewalController;
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

    Route::middleware('role:student')
        ->prefix('student/scholarship-renewals')
        ->name('student.scholarship-renewals.')
        ->group(function () {
            Route::get('/', [ScholarshipRenewalController::class, 'index'])->name('index');
            Route::get('/create', [ScholarshipRenewalController::class, 'create'])->name('create');
            Route::post('/', [ScholarshipRenewalController::class, 'store'])->name('store');
            Route::get('/{application}', [ScholarshipRenewalController::class, 'show'])->name('show');
            Route::patch('/{application}/revise', [ScholarshipRenewalController::class, 'revise'])->name('revise');
            Route::get('/{application}/requirements/{requirement}/download', [ScholarshipRenewalController::class, 'downloadRequirement'])
                ->name('requirements.download');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/official-receipts')
        ->name('admin.official-receipts.')
        ->group(function () {
            Route::get('/', [OfficialReceiptVerificationController::class, 'index'])->name('index');
            Route::get('/{certificateRequest}', [OfficialReceiptVerificationController::class, 'show'])->name('show');
            Route::get('/{certificateRequest}/download', [OfficialReceiptVerificationController::class, 'download'])->name('download');
            Route::patch('/{certificateRequest}/verify', [OfficialReceiptVerificationController::class, 'verify'])->name('verify');
            Route::patch('/{certificateRequest}/approve', [OfficialReceiptVerificationController::class, 'approve'])->name('approve');
            Route::patch('/{certificateRequest}/reject', [OfficialReceiptVerificationController::class, 'reject'])->name('reject');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/certificates')
        ->name('admin.certificates.')
        ->group(function () {
            Route::get('/', [CertificateController::class, 'index'])->name('index');
            Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/monitoring')
        ->name('admin.monitoring.')
        ->group(function () {
            Route::get('/', MonitoringDashboardController::class)->name('dashboard');
            Route::get('/students', [StudentMonitoringController::class, 'index'])->name('students.index');
            Route::get('/students/{student}', [StudentMonitoringController::class, 'show'])->name('students.show');
            Route::patch('/students/{student}', [StudentMonitoringController::class, 'update'])->name('students.update');
            Route::get('/scholars', ScholarRecordMonitoringController::class)->name('scholars.index');
            Route::get('/transactions', TransactionMonitoringController::class)->name('transactions.index');
            Route::get('/programs', [ScholarshipProgramController::class, 'index'])->name('programs.index');
            Route::post('/programs', [ScholarshipProgramController::class, 'store'])->name('programs.store');
            Route::patch('/programs/{program}', [ScholarshipProgramController::class, 'update'])->name('programs.update');
            Route::delete('/programs/{program}', [ScholarshipProgramController::class, 'destroy'])->name('programs.destroy');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/reports')
        ->name('admin.reports.')
        ->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/preview', [ReportController::class, 'preview'])->name('preview');
            Route::get('/export', [ReportController::class, 'export'])->name('export');
        });

    Route::middleware('role:administrator,coordinator')
        ->prefix('evaluator/scholarship-renewals')
        ->name('evaluator.scholarship-renewals.')
        ->group(function () {
            Route::get('/', [ScholarshipRenewalEvaluationController::class, 'index'])->name('index');
            Route::get('/{application}', [ScholarshipRenewalEvaluationController::class, 'show'])->name('show');
            Route::patch('/{application}', [ScholarshipRenewalEvaluationController::class, 'update'])->name('update');
            Route::get('/{application}/requirements/{requirement}/download', [ScholarshipRenewalEvaluationController::class, 'downloadRequirement'])
                ->name('requirements.download');
        });

    Route::middleware('role:scholarship_agency')
        ->prefix('agency/masterlists')
        ->name('agency.masterlists.')
        ->group(function () {
            Route::get('/', [MasterlistController::class, 'index'])->name('index');
            Route::get('/create', [MasterlistController::class, 'create'])->name('create');
            Route::post('/preview', [MasterlistController::class, 'preview'])->name('preview');
            Route::post('/', [MasterlistController::class, 'store'])->name('store');
            Route::get('/{masterlist}', [MasterlistController::class, 'show'])->name('show');
        });

    Route::middleware('role:coordinator')
        ->prefix('coordinator/masterlists')
        ->name('coordinator.masterlists.')
        ->group(function () {
            Route::get('/', [MasterlistValidationController::class, 'index'])->name('index');
            Route::get('/{masterlist}', [MasterlistValidationController::class, 'show'])->name('show');
            Route::patch('/{masterlist}/records/{record}', [MasterlistValidationController::class, 'updateRecord'])
                ->name('records.update');
            Route::post('/{masterlist}/submit', [MasterlistValidationController::class, 'submit'])->name('submit');
        });

    Route::middleware('role:scholarship_chairman')
        ->prefix('chairman/masterlists')
        ->name('chairman.masterlists.')
        ->group(function () {
            Route::get('/', [MasterlistApprovalController::class, 'index'])->name('index');
            Route::get('/{masterlist}', [MasterlistApprovalController::class, 'show'])->name('show');
            Route::patch('/{masterlist}/records/{record}', [MasterlistApprovalController::class, 'updateRecord'])
                ->name('records.update');
            Route::post('/{masterlist}/release', [MasterlistApprovalController::class, 'release'])->name('release');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
