<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\SecurityAuditController;
use App\Http\Controllers\Portal\PaymentReviewController;
use App\Http\Controllers\Portal\SecuritySessionsController;
use App\Http\Controllers\Portal\ClinicFinanceController;
use App\Http\Controllers\Portal\ClinicCashFlowController;
use App\Http\Controllers\Portal\ClinicCashRegisterController;
use App\Http\Controllers\Portal\ClinicRevenueOptimizerController;
use App\Http\Controllers\Portal\ClinicOrganizationController;
use App\Http\Controllers\Portal\ClinicOperationsController;
use App\Http\Controllers\Portal\ClinicBrainController;
use App\Http\Controllers\Portal\ClinicIntelligenceController;
use App\Http\Controllers\Portal\ClinicMarketingController;
use App\Http\Controllers\Portal\MedicalNetworkController;
use App\Http\Controllers\Portal\MedicalCollaborationContractController;
use App\Http\Controllers\Portal\PatientAccessRequestController;
use App\Http\Controllers\Portal\PatientRetentionController;
use App\Http\Controllers\Portal\ClinicGrowthController;
use App\Http\Controllers\Portal\ClinicPerformanceController;
use App\Http\Controllers\Portal\ClinicProfessionalPartnershipController;
use App\Http\Controllers\Portal\ProcurementController;
use App\Http\Controllers\Portal\StaffAttendanceController;
use App\Http\Controllers\Portal\StaffResponsibilitiesController;
use App\Http\Controllers\Portal\StaffCredentialsController;
use App\Http\Controllers\Portal\StaffManagementController;
use App\Http\Controllers\Portal\StaffObligationController;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', function () {
        if (auth('web')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('portal.login');
    })->name('index');

    Route::get('/doctors/{slug}', [
        HomeController::class,
        'HomePage'
    ])->name('doctors.HomePage');

    // Login uses the same web guard as the portal authentication middleware.
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [
            AuthController::class,
            'showLoginForm'
        ])->name('login');

        Route::post('/login', [
            AuthController::class,
            'login'
        ])->middleware('throttle:5,1')->name('login.submit');
    });

    Route::middleware('portal.auth')->group(function () {
        Route::post('/select-doctor/{doctor}', [
            DashboardController::class,
            'selectDoctor'
        ])->name('select-doctor');

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ])->name('logout');

        Route::get('/security/audit-logs', [
            SecurityAuditController::class,
            'index'
        ])->name('security.audit-logs');

        Route::get('/payments/review-queue', [
            PaymentReviewController::class,
            'index'
        ])->name('payments.review-queue');
        Route::get('/payments/refunds', [PaymentReviewController::class, 'refunds'])->name('payments.refunds');
        Route::post('/payments/{payment}/refund', [PaymentReviewController::class, 'refund'])
            ->whereNumber('payment')->middleware('throttle:3,1')->name('payments.refund');

        Route::post('/payments/{payment}/reconcile', [
            PaymentReviewController::class,
            'reconcile'
        ])->whereNumber('payment')->middleware('throttle:3,1')->name('payments.reconcile');

        Route::get('/security/sessions', [
            SecuritySessionsController::class,
            'index'
        ])->name('security.sessions');

        Route::delete('/security/sessions/{session}', [
            SecuritySessionsController::class,
            'destroy'
        ])->where('session', '[a-f0-9]{64}')->name('security.sessions.destroy');

        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ])->name('dashboard');

        Route::get('/clinic-brain', [ClinicBrainController::class, 'index'])->name('clinic-brain.index');
        Route::get('/patient-retention', [PatientRetentionController::class, 'index'])->name('patient-retention.index');
        Route::get('/growth', [ClinicGrowthController::class, 'index'])->name('growth.index');
        Route::get('/clinic-performance', [ClinicPerformanceController::class, 'index'])->name('clinic-performance.index');
        Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
        Route::get('/staff-attendance', [StaffAttendanceController::class, 'index'])->name('staff-attendance.index');
        Route::get('/staff-credentials', [StaffCredentialsController::class, 'index'])->name('staff-credentials.index');
        Route::post('/staff-credentials', [StaffCredentialsController::class, 'store'])->middleware('throttle:10,1')->name('staff-credentials.store');
        Route::get('/staff-credentials/{credential}/download', [StaffCredentialsController::class, 'download'])->whereNumber('credential')->name('staff-credentials.download');
        Route::patch('/staff-credentials/{credential}/review', [StaffCredentialsController::class, 'review'])->whereNumber('credential')->name('staff-credentials.review');
        Route::delete('/staff-credentials/{credential}', [StaffCredentialsController::class, 'archive'])->whereNumber('credential')->name('staff-credentials.archive');

        Route::get('/staff-responsibilities', [StaffResponsibilitiesController::class, 'index'])->name('staff-responsibilities.index');
        Route::post('/staff-responsibilities', [StaffResponsibilitiesController::class, 'store'])->middleware('throttle:20,1')->name('staff-responsibilities.store');
        Route::put('/staff-responsibilities/{responsibility}', [StaffResponsibilitiesController::class, 'update'])->whereNumber('responsibility')->middleware('throttle:20,1')->name('staff-responsibilities.update');
        Route::delete('/staff-responsibilities/{responsibility}', [StaffResponsibilitiesController::class, 'destroy'])->whereNumber('responsibility')->name('staff-responsibilities.destroy');
        Route::get('/staff-management', [StaffManagementController::class, 'index'])->name('staff-management.index');
        Route::post('/staff-management/contracts', [StaffManagementController::class, 'storeContract'])->name('staff-management.contracts.store');
        Route::post('/staff-management/leave-balances', [StaffManagementController::class, 'setLeaveBalance'])->name('staff-management.leave-balances.store');
        Route::post('/staff-management/payrolls', [StaffManagementController::class, 'createPayroll'])->name('staff-management.payrolls.store');
        Route::patch('/staff-management/payrolls/{payroll}/approve', [StaffManagementController::class, 'approvePayroll'])->whereNumber('payroll')->name('staff-management.payrolls.approve');
        Route::post('/staff-management/payrolls/{payroll}/pay', [StaffManagementController::class, 'payPayroll'])->whereNumber('payroll')->name('staff-management.payrolls.pay');
        Route::post('/staff-management/replacements', [StaffManagementController::class, 'assignReplacement'])->name('staff-management.replacements.store');
        Route::post('/staff-management/obligations', [StaffObligationController::class, 'store'])->name('staff-management.obligations.store');
        Route::patch('/staff-management/obligations/{obligation}/approve', [StaffObligationController::class, 'approve'])->whereNumber('obligation')->name('staff-management.obligations.approve');
        Route::post('/staff-management/obligations/{obligation}/disburse', [StaffObligationController::class, 'disburse'])->whereNumber('obligation')->name('staff-management.obligations.disburse');
        Route::post('/staff-management/obligations/{obligation}/repayments', [StaffObligationController::class, 'repay'])->whereNumber('obligation')->name('staff-management.obligations.repayments.store');
        Route::post('/staff-attendance/schedules', [StaffAttendanceController::class, 'storeSchedule'])->name('staff-attendance.schedules.store');
        Route::post('/staff-attendance/check-in', [StaffAttendanceController::class, 'checkIn'])->middleware('throttle:10,1')->name('staff-attendance.check-in');
        Route::post('/staff-attendance/check-out', [StaffAttendanceController::class, 'checkOut'])->middleware('throttle:10,1')->name('staff-attendance.check-out');
        Route::patch('/staff-attendance/{attendance}/correct', [StaffAttendanceController::class, 'correct'])->whereNumber('attendance')->name('staff-attendance.correct');
        Route::post('/staff-attendance/leaves', [StaffAttendanceController::class, 'storeLeave'])->middleware('throttle:10,1')->name('staff-attendance.leaves.store');
        Route::patch('/staff-attendance/leaves/{leave}/decision', [StaffAttendanceController::class, 'decideLeave'])->whereNumber('leave')->name('staff-attendance.leaves.decision');
        Route::post('/staff-attendance/holidays', [StaffAttendanceController::class, 'storeHoliday'])->name('staff-attendance.holidays.store');
        Route::delete('/staff-attendance/holidays/{holiday}', [StaffAttendanceController::class, 'deleteHoliday'])->whereNumber('holiday')->name('staff-attendance.holidays.destroy');
        Route::post('/procurement/suppliers', [ProcurementController::class, 'storeSupplier'])->name('procurement.suppliers.store');
        Route::post('/procurement/quotes', [ProcurementController::class, 'storeQuote'])->name('procurement.quotes.store');
        Route::post('/procurement/quotes/{quote}/order', [ProcurementController::class, 'createOrder'])->whereNumber('quote')->name('procurement.quotes.order');
        Route::patch('/procurement/orders/{order}/approve', [ProcurementController::class, 'approveOrder'])->whereNumber('order')->name('procurement.orders.approve');
        Route::post('/procurement/orders/{order}/items/{item}/receive', [ProcurementController::class, 'receiveItem'])->whereNumber('order')->whereNumber('item')->name('procurement.orders.items.receive');
        Route::post('/procurement/orders/{order}/payments', [ProcurementController::class, 'paySupplier'])->whereNumber('order')->middleware('throttle:10,1')->name('procurement.orders.payments.store');

        Route::get('/patient-access-requests', [PatientAccessRequestController::class, 'index'])->name('patient-access-requests.index');
        Route::patch('/patient-access-requests/{accessRequest}/approve', [PatientAccessRequestController::class, 'approve'])->whereNumber('accessRequest')->name('patient-access-requests.approve');
        Route::patch('/patient-access-requests/{accessRequest}/reject', [PatientAccessRequestController::class, 'reject'])->whereNumber('accessRequest')->name('patient-access-requests.reject');

        Route::get('/professional-partnerships', [ClinicProfessionalPartnershipController::class, 'index'])->name('professional-partnerships.index');
        Route::post('/professional-partnerships', [ClinicProfessionalPartnershipController::class, 'store'])->middleware('throttle:10,1')->name('professional-partnerships.store');
        Route::patch('/professional-partnerships/{partnership}/respond', [ClinicProfessionalPartnershipController::class, 'respond'])->whereNumber('partnership')->name('professional-partnerships.respond');
        Route::post('/professional-partnerships/sessions', [ClinicProfessionalPartnershipController::class, 'storeSession'])->middleware('throttle:20,1')->name('professional-partnerships.sessions.store');
        Route::patch('/professional-partnerships/sessions/{session}/confirm', [ClinicProfessionalPartnershipController::class, 'confirmSession'])->whereNumber('session')->name('professional-partnerships.sessions.confirm');
        Route::post('/professional-partnerships/dues', [ClinicProfessionalPartnershipController::class, 'generateDue'])->middleware('throttle:20,1')->name('professional-partnerships.dues.store');
        Route::patch('/professional-partnerships/dues/{due}/confirm', [ClinicProfessionalPartnershipController::class, 'confirmDue'])->whereNumber('due')->name('professional-partnerships.dues.confirm');
        Route::get('/professional-partnerships/dues/{due}/invoice', [ClinicProfessionalPartnershipController::class, 'invoice'])->whereNumber('due')->name('professional-partnerships.dues.invoice');

        Route::get('/medical-network', [MedicalNetworkController::class, 'index'])->name('medical-network.index');
        Route::get('/medical-network/contracts', [MedicalCollaborationContractController::class, 'index'])->name('medical-network.contracts.index');
        Route::post('/medical-network/contracts', [MedicalCollaborationContractController::class, 'store'])->middleware('throttle:10,1')->name('medical-network.contracts.store');
        Route::patch('/medical-network/contracts/{contract}/respond', [MedicalCollaborationContractController::class, 'respond'])->whereNumber('contract')->name('medical-network.contracts.respond');
        Route::post('/medical-network/contracts/generate-settlements', [MedicalCollaborationContractController::class, 'generate'])->middleware('throttle:5,1')->name('medical-network.contracts.generate');
        Route::patch('/medical-network/settlements/{settlement}/paid', [MedicalCollaborationContractController::class, 'markPaid'])->whereNumber('settlement')->name('medical-network.settlements.paid');
        Route::get('/medical-network/review', [MedicalNetworkController::class, 'review'])->name('medical-network.review');
        Route::patch('/medical-network/providers/{provider}/approve', [MedicalNetworkController::class, 'approveProvider'])->whereNumber('provider')->name('medical-network.providers.approve');
        Route::patch('/medical-network/providers/{provider}/reject', [MedicalNetworkController::class, 'rejectProvider'])->whereNumber('provider')->name('medical-network.providers.reject');
        Route::post('/medical-network/providers', [MedicalNetworkController::class, 'storeProvider'])->name('medical-network.providers.store');
        Route::post('/medical-network/referrals', [MedicalNetworkController::class, 'storeReferral'])->middleware('throttle:20,1')->name('medical-network.referrals.store');
        Route::patch('/medical-network/referrals/{referral}', [MedicalNetworkController::class, 'updateReferral'])->middleware('throttle:30,1')->name('medical-network.referrals.update');

        Route::get('/marketing', [ClinicMarketingController::class, 'index'])->name('marketing.index');
        Route::post('/marketing/campaigns', [ClinicMarketingController::class, 'store'])->name('marketing.store');
        Route::post('/marketing/campaigns/{campaign}/toggle', [ClinicMarketingController::class, 'toggle'])->whereNumber('campaign')->name('marketing.toggle');

        Route::get('/intelligence', [ClinicIntelligenceController::class, 'index'])->name('intelligence.index');
        Route::post('/intelligence/inventory/{item}/settings', [ClinicIntelligenceController::class, 'updateInventorySettings'])->whereNumber('item')->name('intelligence.inventory.settings');
        Route::post('/intelligence/health/programs', [ClinicIntelligenceController::class, 'storeProgram'])->name('intelligence.health.programs.store');
        Route::post('/intelligence/health/enroll', [ClinicIntelligenceController::class, 'enrollPatient'])->name('intelligence.health.enroll');
        Route::post('/intelligence/health/memberships/{membership}/checkup', [ClinicIntelligenceController::class, 'recordPreventiveCheckup'])->whereNumber('membership')->name('intelligence.health.checkup');
        Route::post('/intelligence/health/memberships/{membership}/withdraw', [ClinicIntelligenceController::class, 'withdrawMembership'])->whereNumber('membership')->name('intelligence.health.withdraw');
        Route::post('/intelligence/health/memberships/{membership}/points', [ClinicIntelligenceController::class, 'awardWellnessPoints'])->whereNumber('membership')->name('intelligence.health.points');
        Route::post('/intelligence/economic-simulation', [ClinicIntelligenceController::class, 'simulateEconomics'])->name('intelligence.simulate');

        Route::post('/clinic-brain/waitlist', [ClinicBrainController::class, 'storeWaitlist'])->name('clinic-brain.waitlist.store');
        Route::post('/clinic-brain/waitlist/{entry}/offer', [ClinicBrainController::class, 'offerWaitlistSlot'])->whereNumber('entry')->name('clinic-brain.waitlist.offer');
        Route::post('/clinic-brain/waitlist/{entry}/fulfill', [ClinicBrainController::class, 'fulfillWaitlist'])->whereNumber('entry')->name('clinic-brain.waitlist.fulfill');
        Route::post('/clinic-brain/waitlist/{entry}/close', [ClinicBrainController::class, 'closeWaitlist'])->whereNumber('entry')->name('clinic-brain.waitlist.close');

        Route::get('/operations', [ClinicOperationsController::class, 'index'])->name('operations.index');
        Route::post('/operations/services', [ClinicOperationsController::class, 'storeService'])->name('operations.services.store');
        Route::post('/operations/treatment-plans/{plan}/items', [ClinicOperationsController::class, 'storeTreatmentItem'])->whereNumber('plan')->name('operations.treatment-items.store');
        Route::put('/operations/treatment-items/{item}', [ClinicOperationsController::class, 'updateTreatmentItem'])->whereNumber('item')->name('operations.treatment-items.update');
        Route::post('/operations/dental-chart', [ClinicOperationsController::class, 'storeDentalChart'])->name('operations.dental-chart.store');
        Route::post('/operations/inventory', [ClinicOperationsController::class, 'storeInventoryItem'])->name('operations.inventory.store');
        Route::post('/operations/inventory/{item}/movements', [ClinicOperationsController::class, 'moveStock'])->whereNumber('item')->name('operations.inventory.movements.store');
        Route::post('/operations/settlements', [ClinicOperationsController::class, 'createSettlement'])->name('operations.settlements.store');
        Route::post('/operations/settlements/{settlement}/approve', [ClinicOperationsController::class, 'approveSettlement'])->whereNumber('settlement')->name('operations.settlements.approve');
        Route::post('/operations/settlements/{settlement}/pay', [ClinicOperationsController::class, 'paySettlement'])->whereNumber('settlement')->name('operations.settlements.pay');
        Route::post('/operations/payrolls', [ClinicOperationsController::class, 'createPayroll'])->name('operations.payrolls.store');
        Route::post('/operations/payrolls/{payroll}/approve', [ClinicOperationsController::class, 'approvePayroll'])->whereNumber('payroll')->name('operations.payrolls.approve');
        Route::post('/operations/payrolls/{payroll}/pay', [ClinicOperationsController::class, 'payPayroll'])->whereNumber('payroll')->name('operations.payrolls.pay');

        Route::get('/organizations', [ClinicOrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations', [ClinicOrganizationController::class, 'store'])->name('organizations.store');
        Route::post('/organizations/{organization}/doctors', [ClinicOrganizationController::class, 'inviteDoctor'])->whereNumber('organization')->name('organizations.doctors.invite');
        Route::post('/organizations/{organization}/doctors/accept', [ClinicOrganizationController::class, 'acceptDoctor'])->whereNumber('organization')->name('organizations.doctors.accept');
        Route::delete('/organizations/{organization}/doctors/{doctor}', [ClinicOrganizationController::class, 'removeDoctor'])->whereNumber('organization')->whereNumber('doctor')->name('organizations.doctors.remove');
        Route::post('/organizations/{organization}/clinics', [ClinicOrganizationController::class, 'attachClinic'])->whereNumber('organization')->name('organizations.clinics.attach');
        Route::delete('/organizations/{organization}/clinics/{clinic}', [ClinicOrganizationController::class, 'detachClinic'])->whereNumber('organization')->whereNumber('clinic')->name('organizations.clinics.detach');
        Route::post('/organizations/{organization}/members', [ClinicOrganizationController::class, 'inviteMember'])->whereNumber('organization')->name('organizations.members.invite');
        Route::delete('/organizations/{organization}/members/{member}', [ClinicOrganizationController::class, 'removeMember'])->whereNumber('organization')->whereNumber('member')->name('organizations.members.remove');

        Route::get('/finance', [ClinicFinanceController::class, 'index'])->name('finance.index');
        Route::get('/finance/cash-flow', [ClinicCashFlowController::class, 'index'])->name('finance.cash-flow');
        Route::get('/finance/cash-register', [ClinicCashRegisterController::class, 'index'])->name('finance.cash-register');
        Route::post('/finance/cash-register/open', [ClinicCashRegisterController::class, 'open'])->middleware('throttle:10,1')->name('finance.cash-register.open');
        Route::post('/finance/cash-register/{session}/transactions', [ClinicCashRegisterController::class, 'store'])->whereNumber('session')->middleware('throttle:30,1')->name('finance.cash-register.transactions.store');
        Route::post('/finance/cash-register/{session}/close', [ClinicCashRegisterController::class, 'close'])->whereNumber('session')->name('finance.cash-register.close');
        Route::get('/revenue-optimizer', [ClinicRevenueOptimizerController::class, 'index'])->name('revenue-optimizer.index');
        Route::post('/finance/treatment-plans', [ClinicFinanceController::class, 'storePlan'])->name('finance.plans.store');
        Route::post('/finance/installments/{installment}/payments', [ClinicFinanceController::class, 'recordPayment'])->whereNumber('installment')->name('finance.installments.payments.store');
        Route::post('/finance/treatment-plans/{plan}/revoke-sms-consent', [ClinicFinanceController::class, 'revokeSmsConsent'])->whereNumber('plan')->name('finance.plans.sms-consent.revoke');
        Route::post('/finance/treatment-plans/{plan}/collaterals', [ClinicFinanceController::class, 'storeCollateral'])->whereNumber('plan')->name('finance.collaterals.store');
        Route::put('/finance/collaterals/{collateral}', [ClinicFinanceController::class, 'updateCollateral'])->whereNumber('collateral')->name('finance.collaterals.update');
        Route::post('/finance/expenses', [ClinicFinanceController::class, 'storeExpense'])->name('finance.expenses.store');

        require __DIR__ . '/Profile.php';
        require __DIR__ . '/clinics.php';
        require __DIR__ . '/working-hours.php';
        require __DIR__ . '/gallery.php';
        require __DIR__ . '/patients.php';
        require __DIR__ . '/shifts.php';
        require __DIR__ . '/doctor-reviews.php';
        require __DIR__ . '/Fees.php';
        require __DIR__ . '/Users.php';
    });
});