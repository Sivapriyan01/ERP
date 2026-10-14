<?php
// routes/tenant.php — Tenant Routes
// These run inside each tenant's domain (e.g. demo.trexoerp.in)

use App\Http\Controllers\Tenant\Auth\LoginController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\EmployeeController;
use App\Http\Controllers\Tenant\AttendanceController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\MembershipController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\CalendarController;
use App\Http\Controllers\Tenant\StockTransferController;
use App\Http\Controllers\Tenant\InstalmentController;
use App\Http\Controllers\Tenant\WhatsappController;
use App\Http\Controllers\Tenant\MailController;
use App\Http\Controllers\Tenant\ProductionController;
use App\Http\Controllers\Tenant\SetupController;
use App\Http\Controllers\Tenant\CrmController;
use App\Http\Controllers\Tenant\DailyExpenseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\BusinessReportController;
use App\Http\Controllers\Tenant\ServiceController;
use App\Http\Controllers\Tenant\AccountingController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
| Auto-prefixed with tenant domain by stancl/tenancy
|--------------------------------------------------------------------------
*/

// Redirect root → tenant login
Route::middleware(['tenant_web'])->group(function () {
    Route::get('/public/leads/{slug}', [\App\Http\Controllers\Tenant\CrmPublicController::class, 'show'])->name('crm.public.show');
    Route::post('/public/leads/{slug}', [\App\Http\Controllers\Tenant\CrmPublicController::class, 'submit'])->name('crm.public.submit');
    Route::get('/public/invoice/{bill}', [\App\Http\Controllers\Tenant\BillingController::class, 'publicInvoice'])->name('tenant.public.invoice');

    // Time-Limited HMAC Signed Agreement View (SRS Section 14 & 18)
    Route::get('/agreements/{agreement}/secure-view', [\App\Http\Controllers\Tenant\AgreementController::class, 'secureView'])->name('tenant.agreements.secure-view');



    // Securely serve tenant assets from their sandboxed storage
    Route::get('/tenancy/assets/{path}', function ($path) {
        $realPath = storage_path('app/public/' . $path);
        if (!file_exists($realPath)) {
            abort(404);
        }
        return response()->file($realPath);
    })->where('path', '.*');

    Route::get('/storage/{path}', function ($path) {
        $realPath = storage_path('app/public/' . $path);
        if (!file_exists($realPath)) {
            abort(404);
        }
        return response()->file($realPath);
    })->where('path', '.*');
});

Route::get('/', function () {
    return redirect()->route('tenant.login');
});

// Tenant Auth Routes (Guest) — OWASP A07: brute.force rate-limits login
Route::middleware(['tenant_web', 'guest:tenant'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('tenant.login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('brute.force')
        ->name('tenant.login.post');
});

// Tenant Authenticated Routes
Route::middleware(['tenant_web', 'auth:tenant'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('tenant.dashboard');

    Route::get('/sales', [\App\Http\Controllers\Tenant\SalesController::class, 'index'])->name('tenant.sales.index');

    // Website / Online Orders & Products
    Route::get('/website-orders', [\App\Http\Controllers\Tenant\WebsiteOrdersController::class, 'index'])->name('tenant.website-orders.index');
    Route::post('/website-orders/{bill}/status', [\App\Http\Controllers\Tenant\WebsiteOrdersController::class, 'updateStatus'])->name('tenant.website-orders.status');
    Route::get('/website-products', [\App\Http\Controllers\Tenant\WebsiteProductsController::class, 'index'])->name('tenant.website-products.index');
    Route::post('/website-products/bulk', [\App\Http\Controllers\Tenant\WebsiteProductsController::class, 'bulk'])->name('tenant.website-products.bulk');
    Route::post('/website-products/{id}/toggle', [\App\Http\Controllers\Tenant\WebsiteProductsController::class, 'toggle'])->name('tenant.website-products.toggle');
    Route::get('/website-settings', [\App\Http\Controllers\Tenant\WebsiteSettingsController::class, 'index'])->name('tenant.website-settings.index');
    Route::post('/website-settings', [\App\Http\Controllers\Tenant\WebsiteSettingsController::class, 'update'])->name('tenant.website-settings.update');
    Route::post('/website-settings/test-sms', [\App\Http\Controllers\Tenant\WebsiteSettingsController::class, 'testSms'])->name('tenant.website-settings.test-sms');
    Route::post('/website-settings/seed-samples', [\App\Http\Controllers\Tenant\WebsiteSettingsController::class, 'seedSamples'])->name('tenant.website-settings.seed-samples');

    // Website Coupons & Promo Offers (The Derma Co Style)
    Route::get('/coupons', [\App\Http\Controllers\Tenant\CouponController::class, 'index'])->name('tenant.coupons.index');
    Route::post('/coupons', [\App\Http\Controllers\Tenant\CouponController::class, 'store'])->name('tenant.coupons.store');
    Route::put('/coupons/{coupon}', [\App\Http\Controllers\Tenant\CouponController::class, 'update'])->name('tenant.coupons.update');
    Route::post('/coupons/settings', [\App\Http\Controllers\Tenant\CouponController::class, 'updateSettings'])->name('tenant.coupons.settings');
    Route::post('/coupons/{coupon}/toggle', [\App\Http\Controllers\Tenant\CouponController::class, 'toggle'])->name('tenant.coupons.toggle');
    Route::delete('/coupons/{coupon}', [\App\Http\Controllers\Tenant\CouponController::class, 'destroy'])->name('tenant.coupons.destroy');

    // Gift Cards & Store Vouchers Management
    Route::get('/gift-cards', [\App\Http\Controllers\Tenant\GiftCardController::class, 'index'])->name('tenant.gift-cards.index');
    Route::post('/gift-cards', [\App\Http\Controllers\Tenant\GiftCardController::class, 'store'])->name('tenant.gift-cards.store');
    Route::post('/gift-cards/bulk', [\App\Http\Controllers\Tenant\GiftCardController::class, 'bulkGenerate'])->name('tenant.gift-cards.bulk');
    Route::post('/gift-cards/{giftCard}/toggle', [\App\Http\Controllers\Tenant\GiftCardController::class, 'toggle'])->name('tenant.gift-cards.toggle');
    Route::post('/gift-cards/{giftCard}/unlock', [\App\Http\Controllers\Tenant\GiftCardController::class, 'unlock'])->name('tenant.gift-cards.unlock');
    Route::delete('/gift-cards/{giftCard}', [\App\Http\Controllers\Tenant\GiftCardController::class, 'destroy'])->name('tenant.gift-cards.destroy');
    Route::post('/gift-cards/check', [\App\Http\Controllers\Tenant\GiftCardController::class, 'checkBalance'])->name('tenant.gift-cards.check');
    Route::post('/gift-cards/redeem-pos', [\App\Http\Controllers\Tenant\GiftCardController::class, 'redeemPos'])->name('tenant.gift-cards.redeem-pos');


    Route::get('/ping', function () {
        return 'OK'; });

    // Shared GST Validation (Accessible by Billing, Customers, and Vendors)
    Route::post('/gst/validate', [\App\Http\Controllers\Tenant\GSTController::class, 'validateGstin'])->name('tenant.gst.validate');
    Route::post('/gst/returns', [\App\Http\Controllers\Tenant\GSTController::class, 'fetchReturns'])->name('tenant.gst.returns');

    // Billing & Sales (Outward)
    Route::middleware(['module.permission:Billing'])->prefix('billing')->name('tenant.billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('/quick', [BillingController::class, 'quick'])->name('quick');
        Route::get('/outward', [BillingController::class, 'outward'])->name('outward');
        Route::get('/outward/export', [BillingController::class, 'exportOutward'])->name('outward.export');
        Route::get('/customers/search', [BillingController::class, 'searchCustomer'])->name('search-customer');
        Route::post('/store', [BillingController::class, 'store'])->name('store');
        Route::get('/print/{bill}', [BillingController::class, 'print'])->name('print');
        Route::post('/email/{bill}', [BillingController::class, 'emailInvoice'])->name('email');
        Route::post('/whatsapp/{bill}', [BillingController::class, 'sendWhatsapp'])->name('whatsapp');

        // AJAX
        Route::get('/products/search', [BillingController::class, 'searchProducts'])->name('products.search');
        Route::get('/products/scan', [BillingController::class, 'scanBarcode'])->name('products.scan');
        Route::get('/customer/lookup', [BillingController::class, 'lookupCustomer'])->name('customer.lookup');
        Route::post('/generate', [BillingController::class, 'generateInvoice'])->name('generate');
        Route::get('/invoice/{bill}', [BillingController::class, 'viewInvoice'])->name('invoice.view');
        Route::get('/previous', [BillingController::class, 'previousBills'])->name('previous');
        Route::post('/return', [BillingController::class, 'returnInvoice'])->name('return');
        Route::get('/pre-orders', [BillingController::class, 'preOrders'])->name('pre-orders');
        Route::post('/pre-orders/{bill}/status', [BillingController::class, 'updateOrderStatus'])->name('pre-orders.status');
        Route::post('/pre-orders/{bill}/delivery-date', [BillingController::class, 'updateExpectedDeliveryDate'])->name('pre-orders.delivery-date');
        Route::post('/pre-orders/{bill}/convert', [BillingController::class, 'convertToSalesOrder'])->name('pre-orders.convert');

        // GST Reports
        Route::get('/gst/gstr1', [\App\Http\Controllers\Tenant\GSTController::class, 'gstr1'])->name('gst.gstr1');
        Route::get('/gst/gstr3b', [\App\Http\Controllers\Tenant\GSTController::class, 'gstr3b'])->name('gst.gstr3b');
        Route::get('/gst/download-json', [\App\Http\Controllers\Tenant\GSTController::class, 'downloadJson'])->name('gst.download-json');
    });

    // Return Center
    Route::middleware(['module.permission:Billing'])->name('tenant.returns.')->group(function () {
        Route::get('/returns', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'index'])->name('index');
        Route::get('/returns/customer/invoices', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'getCustomerInvoices'])->name('customer-invoices');
        Route::get('/returns/create', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'create'])->name('create');
        Route::post('/returns', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'store'])->name('store');
        Route::get('/returns/{id}', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'show'])->name('show');
        Route::post('/returns/{id}/status', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'updateStatus'])->name('update-status');
        Route::post('/returns/{id}/item/{itemId}/inspection', [\App\Http\Controllers\Tenant\ReturnCenterController::class, 'updateItemInspection'])->name('update-item-inspection');
    });

    // Delivery Management
    Route::get('/delivery', [\App\Http\Controllers\Tenant\DeliveryController::class, 'index'])->name('tenant.delivery.index');
    Route::post('/delivery/{bill}/status', [\App\Http\Controllers\Tenant\DeliveryController::class, 'updateStatus'])->name('tenant.delivery.status');

    // User management
    Route::middleware(['module.permission:User'])->group(function () {
        Route::resource('users', UserController::class)->names('tenant.users');
    });

    // Employee List management
    Route::middleware(['module.permission:Employee List'])->group(function () {
        Route::resource('employees', EmployeeController::class)->names('tenant.employees');
    });

    // Employee Attendance management
    Route::middleware(['module.permission:Employee Attendance'])->group(function () {
        Route::get('attendance', [AttendanceController::class, 'index'])->name('tenant.attendance.index');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('tenant.attendance.store');
    });

    // Purchase & Stock In (Inward)
    Route::middleware(['module.permission:Purchase'])->name('tenant.purchase.')->group(function () {
        Route::get('/purchase', [PurchaseController::class, 'index'])->name('index');
        Route::get('/purchase-settlement', [PurchaseController::class, 'settlement'])->name('settlement');
        Route::get('/purchase-settlement/export', [PurchaseController::class, 'exportSettlement'])->name('settlement.export');
        Route::get('/purchase-completed', [PurchaseController::class, 'completed'])->name('completed');
        Route::post('/purchase-settlement', [PurchaseController::class, 'storeSettlement'])->name('settlement.store');
        Route::prefix('purchase')->group(function () {
            Route::get('/create', [PurchaseController::class, 'create'])->name('create');
            Route::post('/store', [PurchaseController::class, 'store'])->name('store');
            Route::post('/parse-upload', [PurchaseController::class, 'parseUpload'])->name('parse-upload');
            Route::get('/inward', [PurchaseController::class, 'create'])->name('inward');
            Route::get('/{id}', [PurchaseController::class, 'show'])->name('show');
            Route::get('/{id}/print', [PurchaseController::class, 'printPO'])->name('print');
            Route::get('/{id}/edit', [PurchaseController::class, 'edit'])->name('edit');
            Route::put('/{id}', [PurchaseController::class, 'update'])->name('update');
            Route::delete('/{id}', [PurchaseController::class, 'destroy'])->name('destroy');
        });
    });

    // Masters (Customers & Vendors/Suppliers)
    Route::middleware(['module.permission:Vendors'])->group(function () {
        Route::resource('suppliers', SupplierController::class)->names('tenant.suppliers');
    });
    Route::middleware(['module.permission:Inventory'])->group(function () {
        Route::post('products/import', [ProductController::class, 'import'])->name('tenant.products.import');
        Route::post('products/{id}/toggle-website', [ProductController::class, 'toggleWebsite'])->name('tenant.products.toggle-website');
        Route::resource('products', ProductController::class)->names('tenant.products');
    });
    Route::middleware(['module.permission:Customers'])->group(function () {
        Route::resource('customers', CustomerController::class)->names('tenant.customers');

        // Memberships
        Route::get('membership', [MembershipController::class, 'index'])->name('tenant.membership.index');
        Route::get('membership/plans', [MembershipController::class, 'plans'])->name('tenant.membership.plans');
        Route::post('membership/plans', [MembershipController::class, 'storePlan'])->name('tenant.membership.plans.store');
        Route::put('membership/plans/{plan}', [MembershipController::class, 'updatePlan'])->name('tenant.membership.plans.update');
        Route::delete('membership/plans/{plan}', [MembershipController::class, 'deletePlan'])->name('tenant.membership.plans.destroy');
        Route::post('membership/assign', [MembershipController::class, 'assign'])->name('tenant.membership.assign');
        Route::put('membership/{membership}', [MembershipController::class, 'updateMembership'])->name('tenant.membership.update');
    });
    // Inventory
    Route::middleware(['module.permission:Inventory'])->group(function () {
        Route::get('/inventory', [ProductController::class, 'inventory'])->name('tenant.inventory.index');
        Route::get('/inventory/history', [ProductController::class, 'history'])->name('tenant.inventory.history');
    });
    Route::middleware(['module.permission:Stock Transfer'])->group(function () {
        Route::get('stock-transfer', [StockTransferController::class, 'index'])->name('tenant.stock-transfer.index');
        Route::get('stock-transfer/list', [StockTransferController::class, 'list'])->name('tenant.stock-transfer.list');
        Route::get('stock-transfer/report', [StockTransferController::class, 'report'])->name('tenant.stock-transfer.report');
        Route::post('stock-transfer', [StockTransferController::class, 'store'])->name('tenant.stock-transfer.store');
        Route::post('branches', [\App\Http\Controllers\Tenant\BranchController::class, 'store'])->name('tenant.branches.store');
    });

    // Instalments
    Route::middleware(['module.permission:Instalments'])->group(function () {
        Route::get('instalments', [InstalmentController::class, 'index'])->name('tenant.instalments.index');
        Route::get('instalments/{instalment}', [InstalmentController::class, 'show'])->name('tenant.instalments.show');
        Route::post('instalments/pay', [InstalmentController::class, 'pay'])->name('tenant.instalments.pay');
    });

    // Agreements (AES-256 Encrypted & Cryptographically Sealed)
    Route::middleware(['module.permission:Agreements'])->group(function () {
        Route::get('agreements', [\App\Http\Controllers\Tenant\AgreementController::class, 'index'])->name('tenant.agreements.index');
        Route::post('agreements', [\App\Http\Controllers\Tenant\AgreementController::class, 'store'])->name('tenant.agreements.store');
        Route::get('agreements/{agreement}', [\App\Http\Controllers\Tenant\AgreementController::class, 'show'])->name('tenant.agreements.show');
        Route::put('agreements/{agreement}', [\App\Http\Controllers\Tenant\AgreementController::class, 'update'])->name('tenant.agreements.update');
        Route::post('agreements/{agreement}/status', [\App\Http\Controllers\Tenant\AgreementController::class, 'updateStatus'])->name('tenant.agreements.status');
        Route::delete('agreements/{agreement}', [\App\Http\Controllers\Tenant\AgreementController::class, 'destroy'])->name('tenant.agreements.destroy');
        Route::get('agreements/{agreement}/print', [\App\Http\Controllers\Tenant\AgreementController::class, 'print'])->name('tenant.agreements.print');
        Route::post('agreements/{agreement}/sign', [\App\Http\Controllers\Tenant\AgreementController::class, 'sign'])->name('tenant.agreements.sign');
        Route::post('agreements/{agreement}/share-link', [\App\Http\Controllers\Tenant\AgreementController::class, 'generateShareLink'])->name('tenant.agreements.share-link');
        Route::get('agreements/{agreement}/attachment', [\App\Http\Controllers\Tenant\AgreementController::class, 'downloadAttachment'])->name('tenant.agreements.download-attachment');
        Route::get('agreements/{agreement}/audit-trail', [\App\Http\Controllers\Tenant\AgreementController::class, 'auditTrail'])->name('tenant.agreements.audit-trail');
    });

    // WhatsApp
    Route::middleware(['module.permission:WhatsApp'])->group(function () {
        Route::get('whatsapp', [WhatsappController::class, 'index'])->name('tenant.whatsapp.index');
        Route::post('whatsapp/send', [WhatsappController::class, 'send'])->name('tenant.whatsapp.send');
        Route::post('whatsapp/bulk', [WhatsappController::class, 'sendBulk'])->name('tenant.whatsapp.bulk');
    });

    // Accounting
    Route::prefix('accounting')->name('tenant.accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('index');

        Route::get('/accounts', [AccountingController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [AccountingController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{id}', [AccountingController::class, 'updateAccount'])->name('accounts.update');

        // Journal Entries
        Route::get('/journal-entries', [AccountingController::class, 'journalEntries'])->name('journal-entries');
        Route::get('/journal-entries/create', [AccountingController::class, 'createJournalEntry'])->name('journal-entries.create');
        Route::post('/journal-entries', [AccountingController::class, 'storeJournalEntry'])->name('journal-entries.store');

        // General Ledger
        Route::get('/general-ledger', [AccountingController::class, 'generalLedger'])->name('general-ledger');
    });

    // Production
    Route::middleware(['module.permission:Production'])->prefix('production')->name('tenant.production.')->group(function () {
        Route::get('/', [ProductionController::class, 'index'])->name('index');
        Route::get('/create', [ProductionController::class, 'create'])->name('create');
        Route::post('/store', [ProductionController::class, 'store'])->name('store');
        Route::post('/{production}/accept', [ProductionController::class, 'acceptStage'])->name('accept-stage');
        Route::post('/{production}/notes', [ProductionController::class, 'saveNotes'])->name('save-notes');
        Route::post('/{production}/hold', [ProductionController::class, 'holdJob'])->name('hold');
        Route::post('/{production}/resume', [ProductionController::class, 'resumeJob'])->name('resume');
        Route::post('/{production}/update-stage', [ProductionController::class, 'updateStage'])->name('update-stage');
        Route::post('/scheduler/update-job-stage', [ProductionController::class, 'updateJobStage'])->name('scheduler.update-stage');
        Route::post('/process/store', [ProductionController::class, 'storeProcess'])->name('process.store');
        Route::delete('/process/{process}', [ProductionController::class, 'destroyProcess'])->name('process.destroy');
        // MIS Cost routes
        Route::get('/mis-costs', [ProductionController::class, 'misCosts'])->name('mis-costs');
        Route::post('/mis-costs', [ProductionController::class, 'storeMisCost'])->name('mis-costs.store');
        Route::get('/mis-costs/data', [ProductionController::class, 'getMisCostData'])->name('mis-costs.data');
        Route::get('/mis-costs/details', [ProductionController::class, 'misCostsDetails'])->name('mis-costs.details');
        Route::get('/mis-costs/autofill', [ProductionController::class, 'autofillFromLedger'])->name('mis-costs.autofill');
        Route::get('/analysis/details', [ProductionController::class, 'analysisDetails'])->name('analysis.details');
        // Shift KPI routes
        Route::get('/shift-kpi', [ProductionController::class, 'shiftKpi'])->name('shift-kpi');
        Route::post('/shift-kpi', [ProductionController::class, 'storeShiftKpi'])->name('shift-kpi.store');
    });

    // Mail Marketing
    Route::prefix('mail')->name('tenant.mail.')->group(function () {
        Route::get('/', [MailController::class, 'index'])->name('index');
        Route::post('/sync', [MailController::class, 'sync'])->name('sync');
        Route::post('/broadcast', [MailController::class, 'broadcast'])->name('broadcast');
        Route::post('/accounts', [MailController::class, 'storeAccount'])->name('accounts.store');
        Route::delete('/accounts/{account}', [MailController::class, 'deleteAccount'])->name('accounts.delete');
        Route::post('/toggle-star/{id}', [MailController::class, 'toggleStar'])->name('toggle-star');
    });

    // Summary Dashboard
    Route::prefix('summary')->name('tenant.summary.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\SummaryController::class, 'index'])->name('index');
    });

    // Reports
    Route::prefix('reports')->name('tenant.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/download', [ReportController::class, 'download'])->name('download');
        Route::get('/sales', [ReportController::class, 'index'])->defaults('type', 'invoice')->name('sales');
        Route::get('/stock', [ReportController::class, 'index'])->defaults('type', 'product')->name('stock');
        Route::get('/tax', [ReportController::class, 'index'])->defaults('type', 'gst')->name('tax');
    });

    // Tally Integration
    Route::prefix('tally')->name('tenant.tally.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\TallyController::class, 'index'])->name('index');
        Route::post('/export', [\App\Http\Controllers\Tenant\TallyController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Tenant\TallyController::class, 'import'])->name('import');
        Route::post('/import/process', [\App\Http\Controllers\Tenant\TallyController::class, 'processImport'])->name('import.process');
        Route::post('/settings', [\App\Http\Controllers\Tenant\TallyController::class, 'updateSettings'])->name('settings');
    });

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('tenant.calendar.index');

    // Due Date Dashboard
    Route::get('/due-date-dashboard', [\App\Http\Controllers\Tenant\DueDashboardController::class, 'index'])->name('tenant.due-dashboard.index');

    // Anniversary Reminders
    Route::get('/reminders', [\App\Http\Controllers\Tenant\ReminderController::class, 'index'])->name('tenant.reminders.index');
    Route::post('/reminders/manual', [\App\Http\Controllers\Tenant\ReminderController::class, 'sendManual'])->name('tenant.reminders.manual');

    // CRM Workflow
    Route::prefix('crm')->name('tenant.crm.')->group(function () {
        Route::get('/', [CrmController::class, 'index'])->name('index');
        Route::get('/dashboard', [CrmController::class, 'dashboard'])->name('dashboard');
    Route::get('/sales', [\App\Http\Controllers\Tenant\SalesController::class, 'index'])->name('tenant.sales.index');
        Route::get('/create', [CrmController::class, 'create'])->name('create');
        Route::post('/store', [CrmController::class, 'store'])->name('store');
        Route::get('/export', [CrmController::class, 'export'])->name('export');

        // Lead specific actions
        Route::get('/lead/{lead}/edit', [CrmController::class, 'edit'])->name('lead.edit');
        Route::put('/lead/{lead}', [CrmController::class, 'update'])->name('lead.update');
        Route::delete('/lead/{lead}', [CrmController::class, 'destroy'])->name('lead.destroy');

        // Restrict CRM Settings to admins/users with SetUp permission
        Route::middleware(['module.permission:SetUp'])->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'index'])->name('settings');
            Route::get('/settings/permissions', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'permissions'])->name('settings.permissions');
            Route::get('/settings/permissions/{user}/get', function (\App\Models\TenantUser $user) {
                return response()->json(['permissions' => $user->permissions ?? []]);
            });
            Route::post('/settings/permissions/{user}', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'updatePermissions'])->name('settings.permissions.update');
            Route::get('/settings/workflow', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'workflow'])->name('settings.workflow');
            Route::post('/settings/sync', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'sync'])->name('settings.sync');
            Route::post('/workflow', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'store'])->name('workflow.store');
            Route::post('/workflow/{workflow}/field', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'storeField'])->name('workflow.field.store');

            // Dashboard Settings
            Route::get('/settings/dashboards', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'dashboards'])->name('settings.dashboards');
            Route::post('/settings/dashboards', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'storeDashboard'])->name('settings.dashboards.store');
            Route::delete('/settings/dashboards/{dashboard}', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'deleteDashboard'])->name('settings.dashboards.delete');
            Route::post('/settings/dashboards/{dashboard}/widgets', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'storeWidget'])->name('settings.widgets.store');
            Route::delete('/settings/widgets/{widget}', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'deleteWidget'])->name('settings.widgets.delete');

            // API Settings
            Route::get('/settings/apis', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'apis'])->name('settings.apis');
            Route::post('/settings/apis', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'storeApi'])->name('settings.apis.store');
            Route::delete('/settings/apis/{api}', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'deleteApi'])->name('settings.apis.delete');

            // Public Forms
            Route::get('/settings/public-forms', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'getPublicForms'])->name('settings.public-forms.index');
            Route::delete('/settings/public-forms/{id}', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'deletePublicForm'])->name('settings.public-forms.destroy');
            Route::post('/settings/public-forms', [\App\Http\Controllers\Tenant\WorkflowSettingsController::class, 'storePublicForm'])->name('settings.public-forms.store');
        });
    });

    // Daily Expense
    Route::prefix('daily-expense')->name('tenant.daily-expense.')->group(function () {
        Route::get('/', [DailyExpenseController::class, 'index'])->name('index');
        Route::post('/store', [DailyExpenseController::class, 'store'])->name('store');
        Route::post('/petty-cash', [DailyExpenseController::class, 'storePettyCash'])->name('petty-cash.store');
        Route::delete('/{id}', [DailyExpenseController::class, 'destroy'])->name('destroy');
        Route::get('/export', [DailyExpenseController::class, 'export'])->name('export');
        Route::post('/descriptions', [DailyExpenseController::class, 'storeDescription'])->name('descriptions.store');
        Route::put('/descriptions/{id}', [DailyExpenseController::class, 'updateDescription'])->name('descriptions.update');
        Route::delete('/descriptions/{id}', [DailyExpenseController::class, 'destroyDescription'])->name('descriptions.destroy');
    });

    // Setup / Settings
    Route::get('/setup', [SetupController::class, 'index'])->name('tenant.setup.index');
    Route::post('/setup/unlock', [SetupController::class, 'unlock'])->name('tenant.setup.unlock');
    Route::post('/setup/update', [SetupController::class, 'update'])->name('tenant.setup.update');
    Route::get('/setup/backup', [SetupController::class, 'backup'])->name('tenant.setup.backup');
    Route::post('/setup/restore', [SetupController::class, 'restore'])->name('tenant.setup.restore');

    // AI Assistant
    Route::post('/ai/chat', [\App\Http\Controllers\Tenant\AIChatController::class, 'chat'])->name('tenant.ai.chat');

    // Business Report
    Route::prefix('businessreport')->name('tenant.businessreport.')->group(function () {
        Route::get('/', [BusinessReportController::class, 'index'])->name('index');
        Route::get('/sales', [BusinessReportController::class, 'sales'])->name('sales');
        Route::get('/purchase', [BusinessReportController::class, 'purchase'])->name('purchase');
        Route::get('/stock', [BusinessReportController::class, 'stock'])->name('stock');
        Route::get('/profit', [BusinessReportController::class, 'profit'])->name('profit');
        Route::get('/employee', [BusinessReportController::class, 'employee'])->name('employee');
        Route::get('/customer', [BusinessReportController::class, 'customer'])->name('customer');
        Route::get('/payment', [BusinessReportController::class, 'payment'])->name('payment');
        Route::get('/tax', [BusinessReportController::class, 'tax'])->name('tax');
        Route::get('/financial', [BusinessReportController::class, 'financial'])->name('financial');
        Route::get('/vendor', [BusinessReportController::class, 'vendor'])->name('vendor');
        Route::get('/shift', [BusinessReportController::class, 'shift'])->name('shift');
        Route::get('/product-performance', [BusinessReportController::class, 'productPerformance'])->name('product-performance');
        Route::get('/branch', [BusinessReportController::class, 'branch'])->name('branch');
        Route::get('/discount', [BusinessReportController::class, 'discount'])->name('discount');
        Route::get('/returns', [BusinessReportController::class, 'returns'])->name('returns');
        Route::get('/credit', [BusinessReportController::class, 'credit'])->name('credit');
        Route::get('/production', [BusinessReportController::class, 'production'])->name('production');
        Route::get('/audit', [BusinessReportController::class, 'audit'])->name('audit');
        Route::get('/ai', [BusinessReportController::class, 'ai'])->name('ai');
        Route::get('/analysis', [BusinessReportController::class, 'analysis'])->name('analysis');
        Route::get('/dashboard', [BusinessReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/inventory-movement', [BusinessReportController::class, 'inventoryMovement'])->name('inventory-movement');
        Route::get('/expiry', [BusinessReportController::class, 'expiry'])->name('expiry');
        Route::post('/expiry/dispose', [BusinessReportController::class, 'dispose'])->name('expiry.dispose');
        Route::get('/barcode', [BusinessReportController::class, 'barcode'])->name('barcode');
        Route::get('/price-change', [BusinessReportController::class, 'priceChange'])->name('price-change');
        Route::get('/expense', [BusinessReportController::class, 'expense'])->name('expense');
        Route::get('/cash-register', [BusinessReportController::class, 'cashRegister'])->name('cash-register');
        Route::get('/loyalty', [BusinessReportController::class, 'loyalty'])->name('loyalty');
        Route::get('/delivery', [BusinessReportController::class, 'delivery'])->name('delivery');

        Route::get('/warranty', [BusinessReportController::class, 'warranty'])->name('warranty');
        Route::get('/cancellation', [BusinessReportController::class, 'cancellation'])->name('cancellation');
        Route::get('/reorder', [BusinessReportController::class, 'reorder'])->name('reorder');
        Route::get('/item-tax', [BusinessReportController::class, 'itemTax'])->name('item-tax');
        Route::get('/multi-branch-stock', [BusinessReportController::class, 'multiBranchStock'])->name('multi-branch-stock');
        Route::get('/business-summary', [BusinessReportController::class, 'businessSummary'])->name('business-summary');
    });

    // Standalone Service/Warranty claims management
    Route::get('/services', [ServiceController::class, 'index'])->name('tenant.service.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('tenant.service.store');
    Route::post('/services/{id}/status', [ServiceController::class, 'updateStatus'])->name('tenant.service.update-status');
    Route::delete('/services/{id}', [ServiceController::class, 'destroy'])->name('tenant.service.destroy');
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('tenant.logout')
    ->middleware(['tenant_web', 'auth:tenant']);

// -------------------------------
// Tenant API Routes
// -------------------------------
Route::middleware(['tenant_web'])->prefix('api/v1')->name('tenant.api.public.')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Tenant\Api\AuthController::class, 'login']);
});

Route::middleware(['tenant_web', 'auth:sanctum'])->prefix('api/v1')->name('tenant.api.')->group(function () {

    // ── Billing API ──────────────────────────────────────────────────────────
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'store'])->name('store');
        Route::get('/summary', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'summary'])->name('summary');
        Route::get('/search-customer', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'searchCustomer'])->name('search-customer');
        Route::get('/quick-data', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'quickData'])->name('quick-data');
        Route::get('/{id}', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'show'])->name('show');
        Route::put('/{id}/status', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{id}', [\App\Http\Controllers\Tenant\Api\BillingApiController::class, 'destroy'])->name('destroy');
    });

    // ── Purchase API ────────────────------------------------------------------
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\Api\PurchaseApiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Tenant\Api\PurchaseApiController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Tenant\Api\PurchaseApiController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Tenant\Api\PurchaseApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Tenant\Api\PurchaseApiController::class, 'destroy'])->name('destroy');
    });
    // ── Product API (search/list) ─────────────────────────────────────────────
    Route::get('/pos-products', [\App\Http\Controllers\Tenant\Api\ProductApiController::class, 'index'])->name('products.index');

    // ── Product Master CRUD ───────────────────────────────────────────────────
    Route::prefix('products/master')->name('products.master.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'index'])->name('index');
        Route::post('/',           [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'store'])->name('store');
        Route::get('/categories',  [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'categories'])->name('categories');
        Route::get('/{id}',        [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'show'])->name('show');
        Route::put('/{id}',        [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'update'])->name('update');
        Route::delete('/{id}',     [\App\Http\Controllers\Tenant\Api\ProductMasterApiController::class, 'destroy'])->name('destroy');
    });

    // ── Return API ────────────────────────────────────────────────────────────
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/',    [\App\Http\Controllers\Tenant\Api\ReturnApiController::class, 'index'])->name('index');
        Route::post('/',   [\App\Http\Controllers\Tenant\Api\ReturnApiController::class, 'store'])->name('store');
        Route::get('/{id}',[\App\Http\Controllers\Tenant\Api\ReturnApiController::class, 'show'])->name('show');
    });

    // ── Inventory API ─────────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'index'])->name('index');
        Route::get('/summary',       [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'summary'])->name('summary');
        Route::get('/history',       [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'history'])->name('history');
        Route::get('/low-stock',     [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'lowStock'])->name('low-stock');
        Route::get('/categories',    [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'categories'])->name('categories');
        Route::get('/valuation',     [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'valuation'])->name('valuation');
        Route::post('/bulk-adjust',  [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'bulkAdjust'])->name('bulk-adjust');
        Route::get('/{id}',          [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::put('/{id}/adjust',   [\App\Http\Controllers\Tenant\Api\InventoryApiController::class, 'adjust'])->name('adjust')->where('id', '[0-9]+');
    });

    // ── Customers API ─────────────────────────────────────────────────────────
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'index'])->name('index');
        Route::post('/',           [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'store'])->name('store');
        Route::get('/{id}',        [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'show'])->name('show');
        Route::put('/{id}',        [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'update'])->name('update');
        Route::delete('/{id}',     [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/bills',  [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'bills'])->name('bills');
        Route::get('/{id}/ledger', [\App\Http\Controllers\Tenant\Api\CustomerApiController::class, 'ledger'])->name('ledger');
    });

    // ── Vendors API ───────────────────────────────────────────────────────────
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'index'])->name('index');
        Route::post('/',              [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'store'])->name('store');
        Route::get('/{id}',           [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'show'])->name('show');
        Route::put('/{id}',           [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'update'])->name('update');
        Route::delete('/{id}',        [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/purchases', [\App\Http\Controllers\Tenant\Api\VendorApiController::class, 'purchases'])->name('purchases');
    });

    // ── Employees + Attendance API ────────────────────────────────────────────
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'index'])->name('index');
        Route::post('/',             [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'store'])->name('store');
        Route::get('/attendance',    [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'attendance'])->name('attendance');
        Route::post('/attendance',   [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'storeAttendance'])->name('attendance.store');
        Route::get('/{id}',          [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'show'])->name('show');
        Route::put('/{id}',          [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'update'])->name('update');
        Route::delete('/{id}',       [\App\Http\Controllers\Tenant\Api\EmployeeApiController::class, 'destroy'])->name('destroy');
    });

    // ── Stock Transfer API ────────────────────────────────────────────────────
    Route::prefix('stock-transfer')->name('stock-transfer.')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Tenant\Api\StockTransferApiController::class, 'index'])->name('index');
        Route::post('/',       [\App\Http\Controllers\Tenant\Api\StockTransferApiController::class, 'store'])->name('store');
        Route::get('/report',  [\App\Http\Controllers\Tenant\Api\StockTransferApiController::class, 'report'])->name('report');
        Route::get('/{id}',    [\App\Http\Controllers\Tenant\Api\StockTransferApiController::class, 'show'])->name('show');
    });

    // ── Membership API ────────────────────────────────────────────────────────
    Route::prefix('membership')->name('membership.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'index'])->name('index');
        Route::post('/assign',    [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'assign'])->name('assign');
        Route::put('/{id}',       [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'update'])->name('update');
        Route::get('/plans',      [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'plans'])->name('plans');
        Route::post('/plans',     [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{id}', [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/plans/{id}', [\App\Http\Controllers\Tenant\Api\MembershipApiController::class, 'destroyPlan'])->name('plans.destroy');
    });

    // ── Instalments API ───────────────────────────────────────────────────────
    Route::prefix('instalments')->name('instalments.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\Tenant\Api\InstalmentApiController::class, 'index'])->name('index');
        Route::post('/pay',      [\App\Http\Controllers\Tenant\Api\InstalmentApiController::class, 'pay'])->name('pay');
        Route::get('/due-today', [\App\Http\Controllers\Tenant\Api\InstalmentApiController::class, 'dueToday'])->name('due-today');
        Route::get('/{id}',      [\App\Http\Controllers\Tenant\Api\InstalmentApiController::class, 'show'])->name('show');
    });

    // ── Accounting API ────────────────────────────────────────────────────────
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/accounts',         [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'accounts'])->name('accounts');
        Route::post('/accounts',        [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{id}',    [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{id}', [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::get('/journal-entries',       [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'journalEntries'])->name('journal-entries');
        Route::post('/journal-entries',      [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'storeJournalEntry'])->name('journal-entries.store');
        Route::get('/journal-entries/{id}',  [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'showJournalEntry'])->name('journal-entries.show');
        Route::get('/general-ledger',        [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'generalLedger'])->name('general-ledger');
        Route::get('/trial-balance',         [\App\Http\Controllers\Tenant\Api\AccountingApiController::class, 'trialBalance'])->name('trial-balance');
    });

    // ── CRM API ───────────────────────────────────────────────────────────────
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/dashboard',  [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'dashboard'])->name('dashboard');
        Route::get('/statuses',   [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'statuses'])->name('statuses');
        Route::get('/leads',      [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'index'])->name('leads');
        Route::post('/leads',     [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'store'])->name('leads.store');
        Route::get('/leads/{id}', [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'show'])->name('leads.show');
        Route::put('/leads/{id}', [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{id}', [\App\Http\Controllers\Tenant\Api\CrmApiController::class, 'destroy'])->name('leads.destroy');
    });

    // ── Services API ──────────────────────────────────────────────────────────
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/',           [\App\Http\Controllers\Tenant\Api\ServiceApiController::class, 'index'])->name('index');
        Route::post('/',          [\App\Http\Controllers\Tenant\Api\ServiceApiController::class, 'store'])->name('store');
        Route::get('/{id}',       [\App\Http\Controllers\Tenant\Api\ServiceApiController::class, 'show'])->name('show');
        Route::post('/{id}/status', [\App\Http\Controllers\Tenant\Api\ServiceApiController::class, 'updateStatus'])->name('status');
        Route::delete('/{id}',    [\App\Http\Controllers\Tenant\Api\ServiceApiController::class, 'destroy'])->name('destroy');
    });

    // ── Delivery API ──────────────────────────────────────────────────────────
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Tenant\Api\DeliveryApiController::class, 'index'])->name('index');
        Route::post('/{bill}/status',  [\App\Http\Controllers\Tenant\Api\DeliveryApiController::class, 'updateStatus'])->name('status');
    });

    // ── Anniversary/Reminder API ──────────────────────────────────────────────
    Route::prefix('reminders')->name('reminders.')->group(function () {
        Route::get('/upcoming', [\App\Http\Controllers\Tenant\Api\AnniversaryApiController::class, 'upcoming'])->name('upcoming');
        Route::get('/today',    [\App\Http\Controllers\Tenant\Api\AnniversaryApiController::class, 'today'])->name('today');
        Route::post('/send',    [\App\Http\Controllers\Tenant\Api\AnniversaryApiController::class, 'send'])->name('send');
    });

    // ── Manufacturing API ─────────────────────────────────────────────────────
    Route::prefix('manufacturing')->name('manufacturing.')->group(function () {
        Route::get('/',             [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'index'])->name('index');
        Route::post('/',            [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'store'])->name('store');
        Route::get('/summary',      [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'summary'])->name('summary');
        Route::get('/processes',    [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'processes'])->name('processes');
        Route::get('/{id}',         [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'show'])->name('show');
        Route::put('/{id}',         [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'update'])->name('update');
        Route::post('/{id}/status', [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'updateStatus'])->name('status');
        Route::delete('/{id}',      [\App\Http\Controllers\Tenant\Api\ManufacturingApiController::class, 'destroy'])->name('destroy');
    });

    // ── Setup/Settings API ────────────────────────────────────────────────────
    Route::prefix('setup')->name('setup.')->group(function () {
        Route::get('/',         [\App\Http\Controllers\Tenant\Api\SetupApiController::class, 'index'])->name('index');
        Route::put('/',         [\App\Http\Controllers\Tenant\Api\SetupApiController::class, 'update'])->name('update');
        Route::get('/backup',   [\App\Http\Controllers\Tenant\Api\SetupApiController::class, 'backup'])->name('backup');
        Route::get('/modules',  [\App\Http\Controllers\Tenant\Api\SetupApiController::class, 'modules'])->name('modules');
        Route::get('/business', [\App\Http\Controllers\Tenant\Api\SetupApiController::class, 'business'])->name('business');
    });

    // ── Business Reports API ──────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/dashboard',            [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'dashboard'])->name('dashboard');
        Route::get('/sales',                [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'sales'])->name('sales');
        Route::get('/purchase',             [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'purchase'])->name('purchase');
        Route::get('/profit',               [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'profit'])->name('profit');
        Route::get('/stock',                [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'stock'])->name('stock');
        Route::get('/customers',            [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'customers'])->name('customers');
        Route::get('/product-performance',  [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'productPerformance'])->name('product-performance');
        Route::get('/payment',              [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'payment'])->name('payment');
        Route::get('/tax',                  [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'tax'])->name('tax');
        Route::get('/expense',              [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'expense'])->name('expense');
        Route::get('/credit',               [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'credit'])->name('credit');
        Route::get('/vendor',               [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'vendor'])->name('vendor');
        Route::get('/discount',             [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'discount'])->name('discount');
        Route::get('/returns',              [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'returns'])->name('returns');
        Route::get('/financial',            [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'financial'])->name('financial');
        Route::get('/inventory-movement',   [\App\Http\Controllers\Tenant\Api\BusinessReportApiController::class, 'inventoryMovement'])->name('inventory-movement');
    });

    // ── WhatsApp API ──────────────────────────────────────────────────────────
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::post('/send',          [\App\Http\Controllers\Tenant\Api\WhatsappApiController::class, 'send'])->name('send');
        Route::post('/bulk',          [\App\Http\Controllers\Tenant\Api\WhatsappApiController::class, 'bulk'])->name('bulk');
        Route::post('/send-invoice',  [\App\Http\Controllers\Tenant\Api\WhatsappApiController::class, 'sendInvoice'])->name('send-invoice');
        Route::get('/templates',      [\App\Http\Controllers\Tenant\Api\WhatsappApiController::class, 'templates'])->name('templates');
    });

    // ── Mail API ──────────────────────────────────────────────────────────────
    Route::prefix('mail')->name('mail.')->group(function () {
        Route::get('/accounts',       [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'accounts'])->name('accounts');
        Route::post('/accounts',      [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'storeAccount'])->name('accounts.store');
        Route::delete('/accounts/{id}', [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/send',          [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'send'])->name('send');
        Route::post('/broadcast',     [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'broadcast'])->name('broadcast');
        Route::get('/logs',           [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'logs'])->name('logs');
        Route::post('/sync',          [\App\Http\Controllers\Tenant\Api\MailApiController::class, 'sync'])->name('sync');
    });

    // ── Calendar API ──────────────────────────────────────────────────────────
    Route::get('/calendar', [\App\Http\Controllers\Tenant\Api\CalendarApiController::class, 'index'])->name('calendar');

    // ── Due Invoices API ──────────────────────────────────────────────────────
    Route::prefix('due-invoices')->name('due-invoices.')->group(function () {
        Route::get('/',          [\App\Http\Controllers\Tenant\Api\DueInvoiceApiController::class, 'index'])->name('index');
        Route::get('/summary',   [\App\Http\Controllers\Tenant\Api\DueInvoiceApiController::class, 'summary'])->name('summary');
        Route::post('/{id}/pay', [\App\Http\Controllers\Tenant\Api\DueInvoiceApiController::class, 'pay'])->name('pay');
    });

    // ── User Management API ───────────────────────────────────────────────────
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',                  [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'index'])->name('index');
        Route::post('/',                 [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'store'])->name('store');
        Route::get('/{id}',              [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'show'])->name('show');
        Route::put('/{id}',              [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'update'])->name('update');
        Route::delete('/{id}',           [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/permissions',  [\App\Http\Controllers\Tenant\Api\UserApiController::class, 'permissions'])->name('permissions');
    });

    // ── Tally ERP API ─────────────────────────────────────────────────────────
    Route::prefix('tally')->name('tally.')->group(function () {
        Route::get('/',                 [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'index'])->name('index');
        Route::get('/settings',         [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'settings'])->name('settings');
        Route::post('/settings',        [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'updateSettings'])->name('settings.update');
        Route::get('/export/preview',   [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'exportPreview'])->name('export.preview');
        Route::post('/export',          [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'export'])->name('export');
        Route::get('/import/status',    [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'importStatus'])->name('import.status');
        Route::get('/logs',             [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'logs'])->name('logs');
        Route::post('/validate',        [\App\Http\Controllers\Tenant\Api\TallyApiController::class, 'validate'])->name('validate');
    });
});
