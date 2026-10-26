<?php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\Monitoring\AuditLogController;
use App\Http\Controllers\Admin\Monitoring\MonitoringDashboardController;
use App\Http\Controllers\Admin\Monitoring\ScholarRecordMonitoringController;
use App\Http\Controllers\Admin\Monitoring\StudentMonitoringController;
use App\Http\Controllers\Admin\Monitoring\TransactionMonitoringController;
use App\Http\Controllers\Admin\OfficialReceiptVerificationController;
use App\Http\Controllers\Admin\Reports\ReportController;
use App\Http\Controllers\Admin\ScholarshipOpportunityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Chairman\GranteeReconciliationController;
use App\Http\Controllers\Chairman\GranteeUploadController;
use App\Http\Controllers\Chairman\MasterlistApprovalController;
use App\Http\Controllers\Chairman\MasterlistUploadController;
use App\Http\Controllers\Coordinator\GranteeCoordinatorController;
use App\Http\Controllers\Coordinator\MasterlistValidationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Registrar\EnrolledStudentController;
use App\Http\Controllers\Registrar\GranteeRegistrarVerificationController;
use App\Http\Controllers\Registrar\MasterlistVerificationController as RegistrarMasterlistVerificationController;
use App\Http\Controllers\Student\CertificateRequestController;
use App\Http\Controllers\Student\ScholarshipDiscoveryController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class);
Route::get('/scholarships/{policy}/guidelines', [WelcomeController::class, 'downloadGuidelines'])->name('scholarships.guidelines');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')
        ->name('dashboard.student');

    Route::get('/administrator/dashboard', [DashboardController::class, 'administrator'])
        ->middleware('role:administrator')
        ->name('dashboard.administrator');

    Route::get('/coordinator/dashboard', [DashboardController::class, 'coordinator'])
        ->middleware('role:coordinator')
        ->name('dashboard.coordinator');

    Route::get('/scholarship-chairman/dashboard', [DashboardController::class, 'scholarshipChairman'])
        ->middleware('role:scholarship_chairman')
        ->name('dashboard.scholarship-chairman');

    Route::get('/registrar/dashboard', [DashboardController::class, 'registrar'])
        ->middleware('role:registrar')
        ->name('dashboard.registrar');

    Route::middleware('role:administrator,scholarship_chairman')
        ->prefix('admin/scholarships')
        ->name('admin.scholarships.')
        ->group(function () {
            Route::get('/', [ScholarshipOpportunityController::class, 'index'])->name('index');
            Route::post('/', [ScholarshipOpportunityController::class, 'store'])->name('store');
            Route::patch('/{policy}', [ScholarshipOpportunityController::class, 'update'])->name('update');
            Route::delete('/{policy}', [ScholarshipOpportunityController::class, 'destroy'])->name('destroy');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/users')
        ->name('admin.users.')
        ->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::patch('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

    Route::middleware('role:student')
        ->prefix('student/scholarships')
        ->name('student.scholarships.')
        ->group(function () {
            Route::get('/', [ScholarshipDiscoveryController::class, 'index'])->name('index');
            Route::get('/{policy}/download', [ScholarshipDiscoveryController::class, 'download'])->name('download');
        });
    Route::middleware('role:student')
        ->prefix('student/certificate-requests')
        ->name('student.certificate-requests.')
        ->group(function () {
            Route::get('/', [CertificateRequestController::class, 'index'])->name('index');
            Route::get('/create', [CertificateRequestController::class, 'create'])->name('create');
            Route::post('/', [CertificateRequestController::class, 'store'])->name('store');
            Route::get('/{certificateRequest}', [CertificateRequestController::class, 'show'])->name('show');
            Route::get('/{certificateRequest}/official-receipt/view', [CertificateRequestController::class, 'viewOfficialReceipt'])
                ->name('official-receipt.view');
            Route::get('/{certificateRequest}/official-receipt/download', [CertificateRequestController::class, 'downloadOfficialReceipt'])
                ->name('official-receipt.download');
            Route::get('/{certificateRequest}/certificate/view', [CertificateRequestController::class, 'viewCertificate'])
                ->name('certificate.view');
            Route::get('/{certificateRequest}/certificate', [CertificateRequestController::class, 'downloadCertificate'])
                ->name('certificate.download');
        });

    Route::middleware('role:administrator,scholarship_chairman')
        ->prefix('admin/official-receipts')
        ->name('admin.official-receipts.')
        ->group(function () {
            Route::get('/', [OfficialReceiptVerificationController::class, 'index'])->name('index');
            Route::get('/{certificateRequest}', [OfficialReceiptVerificationController::class, 'show'])->name('show');
            Route::get('/{certificateRequest}/view', [OfficialReceiptVerificationController::class, 'view'])->name('view');
            Route::get('/{certificateRequest}/download', [OfficialReceiptVerificationController::class, 'download'])->name('download');
            Route::get('/{certificateRequest}/preview-certificate', [OfficialReceiptVerificationController::class, 'previewCertificate'])->name('preview-certificate');
            Route::patch('/{certificateRequest}/verify', [OfficialReceiptVerificationController::class, 'verify'])->name('verify');
            Route::patch('/{certificateRequest}/approve', [OfficialReceiptVerificationController::class, 'approve'])->name('approve');
            Route::patch('/{certificateRequest}/reject', [OfficialReceiptVerificationController::class, 'reject'])->name('reject');
        });

    Route::middleware('role:administrator,scholarship_chairman')
        ->prefix('admin/certificates')
        ->name('admin.certificates.')
        ->group(function () {
            Route::get('/', [CertificateController::class, 'index'])->name('index');
            Route::get('/{certificate}/view', [CertificateController::class, 'view'])->name('view');
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
            Route::get('/audit', AuditLogController::class)->name('audit.index');
        });

    Route::middleware('role:administrator')
        ->prefix('admin/reports')
        ->name('admin.reports.')
        ->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/preview', [ReportController::class, 'preview'])->name('preview');
            Route::get('/export', [ReportController::class, 'export'])->name('export');
        });

    Route::middleware('role:coordinator')
        ->prefix('coordinator/masterlists')
        ->name('coordinator.masterlists.')
        ->group(function () {
            Route::get('/', [MasterlistValidationController::class, 'index'])->name('index');
        });
    Route::middleware('role:coordinator')->prefix('coordinator/masterlist-batches')->name('coordinator.batches.')->group(function () {
        Route::get('/{batch}', [MasterlistValidationController::class, 'showBatch'])->name('show');
        Route::post('/{batch}/submit-to-registrar', [MasterlistValidationController::class, 'submitToRegistrar'])->name('submit-to-registrar');
        Route::post('/{batch}/retry-verification', [MasterlistValidationController::class, 'retryVerification'])->name('retry-verification');
        Route::post('/{batch}/submit-to-chairman', [MasterlistValidationController::class, 'submitToChairman'])->name('submit-to-chairman');
    });

    Route::middleware('role:coordinator')
        ->prefix('coordinator/grantees')
        ->name('coordinator.grantees.')
        ->group(function () {
            Route::get('/', [GranteeCoordinatorController::class, 'index'])->name('index');
            Route::get('/{batch}', [GranteeCoordinatorController::class, 'show'])->name('show');
            Route::get('/{batch}/export', [GranteeCoordinatorController::class, 'export'])->name('export');
            Route::post('/{batch}/forward-to-registrar', [GranteeCoordinatorController::class, 'forwardToRegistrar'])->name('forward-to-registrar');
            Route::post('/{batch}/endorse', [GranteeCoordinatorController::class, 'endorse'])->name('endorse');
        });

    Route::middleware('role:scholarship_chairman')
        ->prefix('chairman/uploads')
        ->name('chairman.uploads.')
        ->group(function () {
            Route::get('/', [MasterlistUploadController::class, 'index'])->name('index');
            Route::get('/create', [MasterlistUploadController::class, 'create'])->name('create');
            Route::post('/preview', [MasterlistUploadController::class, 'preview'])->name('preview');
            Route::post('/', [MasterlistUploadController::class, 'store'])->name('store');
            Route::get('/{masterlist}', [MasterlistUploadController::class, 'show'])->name('show');
        });

    Route::middleware('role:scholarship_chairman')
        ->prefix('chairman/masterlists')
        ->name('chairman.masterlists.')
        ->group(function () {
            Route::get('/', [MasterlistApprovalController::class, 'index'])->name('index');
            Route::get('/{masterlist}', [MasterlistApprovalController::class, 'show'])->name('show');
            Route::post('/{masterlist}/release', [MasterlistApprovalController::class, 'release'])->name('release');
            Route::get('/{masterlist}/export', [MasterlistApprovalController::class, 'export'])->name('export');
        });

    Route::middleware('role:scholarship_chairman')
        ->prefix('chairman/grantees')
        ->name('chairman.grantees.')
        ->group(function () {
            Route::get('/', [GranteeReconciliationController::class, 'index'])->name('index');
            Route::get('/upload', [GranteeUploadController::class, 'create'])->name('upload');
            Route::post('/upload/parse', [GranteeUploadController::class, 'parse'])->name('upload.parse');
            Route::post('/upload', [GranteeUploadController::class, 'store'])->name('upload.store');
            Route::get('/students/search', [GranteeReconciliationController::class, 'searchStudents'])->name('students.search');
            Route::get('/{batch}', [GranteeReconciliationController::class, 'show'])->name('show');
            Route::get('/{batch}/export', [GranteeReconciliationController::class, 'export'])->name('export');
            Route::post('/{batch}/verify', [GranteeReconciliationController::class, 'verify'])->name('verify');
            Route::patch('/{batch}/records/{record}/resolve', [GranteeReconciliationController::class, 'resolve'])->name('records.resolve');
            Route::patch('/{batch}/records/{record}/assign-campus', [GranteeReconciliationController::class, 'assignCampus'])->name('records.assign-campus');
            Route::post('/{batch}/assign-fallback-campus', [GranteeReconciliationController::class, 'assignFallbackCampus'])->name('assign-fallback-campus');
            Route::post('/{batch}/deliver-to-coordinator', [GranteeReconciliationController::class, 'deliverToCoordinator'])->name('deliver-to-coordinator');
            Route::post('/{batch}/commit', [GranteeReconciliationController::class, 'commit'])->name('commit');
        });

    Route::middleware('role:scholarship_chairman,administrator')
        ->prefix('chairman/scholars')
        ->name('chairman.scholars.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Chairman\ScholarDirectoryController::class, 'index'])->name('index');
        });

    Route::middleware('role:registrar')
        ->prefix('registrar/enrolled-students')
        ->name('registrar.enrolled-students.')
        ->group(function () {
            Route::get('/', [EnrolledStudentController::class, 'index'])->name('index');
            Route::get('/template', [EnrolledStudentController::class, 'downloadTemplate'])->name('template');
            Route::post('/import', [EnrolledStudentController::class, 'import'])->name('import');
            Route::delete('/clear', [EnrolledStudentController::class, 'clearCampusRecords'])->name('clear');
            Route::delete('/{student}', [EnrolledStudentController::class, 'destroy'])->name('destroy');
        });
    Route::middleware('role:registrar')->prefix('registrar/masterlist-batches')->name('registrar.batches.')->group(function () {
        Route::get('/', [RegistrarMasterlistVerificationController::class, 'index'])->name('index');
        Route::get('/{batch}', [RegistrarMasterlistVerificationController::class, 'show'])->name('show');
        Route::get('/{batch}/official-records', [RegistrarMasterlistVerificationController::class, 'officialRecords'])->name('official-records');
        Route::post('/{batch}/reverify', [RegistrarMasterlistVerificationController::class, 'reverify'])->name('reverify');
        Route::patch('/{batch}/records/{record}', [RegistrarMasterlistVerificationController::class, 'update'])->name('records.update');
        Route::post('/{batch}/return', [RegistrarMasterlistVerificationController::class, 'return'])->name('return');
    });

    Route::middleware('role:registrar')
        ->prefix('registrar/grantees')
        ->name('registrar.grantees.')
        ->group(function () {
            Route::get('/', [GranteeRegistrarVerificationController::class, 'index'])->name('index');
            Route::get('/{batch}', [GranteeRegistrarVerificationController::class, 'show'])->name('show');
            Route::get('/{batch}/export', [GranteeRegistrarVerificationController::class, 'export'])->name('export');
            Route::post('/{batch}/verify-batch', [GranteeRegistrarVerificationController::class, 'verifyBatch'])->name('verify-batch');
            Route::patch('/{batch}/records/{record}', [GranteeRegistrarVerificationController::class, 'updateRecord'])->name('records.update');
            Route::post('/{batch}/return-to-coordinator', [GranteeRegistrarVerificationController::class, 'returnToCoordinator'])->name('return-to-coordinator');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/student-details', [ProfileController::class, 'updateStudentDetails'])->name('profile.student-details.update');
    Route::patch('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile/signature', [ProfileController::class, 'destroySignature'])->name('profile.signature.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications/counts', \App\Http\Controllers\NotificationCountController::class)->name('notifications.counts');
});

require __DIR__ . '/auth.php';
