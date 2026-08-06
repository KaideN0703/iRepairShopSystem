<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\ProgressUpdateController;
use App\Http\Controllers\CustomerApprovalController;
use App\Http\Controllers\PhotoCommentController;

// Public Customer Portal & Live Tracking Routes
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/status', [CustomerPortalController::class, 'index'])->name('status.index');
Route::post('/status/lookup', [CustomerPortalController::class, 'lookup'])->name('status.lookup');
Route::get('/status/{ticket_number}', [CustomerPortalController::class, 'show'])->name('status.show');
Route::get('/track/{token}', [CustomerPortalController::class, 'track'])->name('track.show');
Route::get('/track/{token}/progress-data', [CustomerPortalController::class, 'progressUpdates'])->name('track.progress_updates');

// Public Customer Approval Response Route (No Auth Needed)
Route::post('/track/{token}/respond/{approval_request}', [CustomerApprovalController::class, 'respond'])->name('customer.approval.respond');

// Public Photo Comments Routes (Customer side & fetch)
Route::post('/track/{token}/photo-comments', [PhotoCommentController::class, 'storeCustomerComment'])->name('customer.photo_comments.store');
Route::get('/photo-comments/{photo_type}/{photo_id}', [PhotoCommentController::class, 'getPhotoComments'])->name('photo_comments.index');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Customers
    Route::resource('customers', CustomerController::class);

    // Devices
    Route::resource('devices', DeviceController::class);

    // Job Orders
    Route::resource('job_orders', JobOrderController::class);
    Route::patch('/job_orders/{job_order}/status', [JobOrderController::class, 'updateStatus'])->name('job_orders.update_status');
    Route::post('/job_orders/{job_order}/assign_technician', [JobOrderController::class, 'assignTechnician'])->name('job_orders.assign_technician');
    Route::post('/job_orders/{job_order}/parts', [JobOrderController::class, 'addPart'])->name('job_orders.add_part');
    Route::delete('/job_orders/{job_order}/parts/{job_order_part}', [JobOrderController::class, 'removePart'])->name('job_orders.remove_part');
    Route::patch('/job_orders/{job_order}/costs', [JobOrderController::class, 'updateCosts'])->name('job_orders.update_costs');
    Route::get('/job_orders/{job_order}/receipt', [JobOrderController::class, 'printReceipt'])->name('job_orders.receipt');
    Route::post('/job_orders/{job_order}/photos', [JobOrderController::class, 'uploadPhoto'])->name('job_orders.upload_photo');
    Route::post('/job_orders/{job_order}/signature', [JobOrderController::class, 'saveSignature'])->name('job_orders.save_signature');

    // Live Repair Progress Updates (Staff Side)
    Route::post('/job_orders/{job_order}/progress_updates', [ProgressUpdateController::class, 'store'])->name('job_orders.progress_updates.store');
    Route::get('/job_orders/{job_order}/progress_updates', [ProgressUpdateController::class, 'index'])->name('job_orders.progress_updates.index');
    Route::post('/job_orders/{job_order}/photo-comments', [PhotoCommentController::class, 'storeStaffComment'])->name('job_orders.photo_comments.store');

    // Diagnosis
    Route::get('/job_orders/{job_order}/diagnosis/create', [DiagnosisController::class, 'create'])->name('diagnoses.create');
    Route::post('/job_orders/{job_order}/diagnosis', [DiagnosisController::class, 'store'])->name('diagnoses.store');
    Route::post('/job_orders/{job_order}/diagnosis/ai_suggestions', [DiagnosisController::class, 'getAiSuggestions'])->name('diagnoses.ai_suggestions');

    // Inventory Parts
    Route::resource('inventory', InventoryController::class);
    Route::post('/inventory/{part}/stock', [InventoryController::class, 'adjustStock'])->name('inventory.adjust_stock');
    Route::get('/inventory/{part}/barcode', [InventoryController::class, 'printBarcode'])->name('inventory.barcode');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{supplier}/purchase_orders/create', [SupplierController::class, 'createPurchaseOrder'])->name('suppliers.create_po');
    Route::post('/suppliers/{supplier}/purchase_orders', [SupplierController::class, 'storePurchaseOrder'])->name('suppliers.store_po');

    // Technicians
    Route::resource('technicians', TechnicianController::class);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/job_orders/{job_order}/generate_invoice', [InvoiceController::class, 'generateFromJob'])->name('invoices.generate');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.record_payment');
    Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'printReceipt'])->name('invoices.receipt');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Warranties
    Route::get('/warranties', [WarrantyController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/{warranty}', [WarrantyController::class, 'show'])->name('warranties.show');
    Route::post('/warranties/{warranty}/claims', [WarrantyController::class, 'fileClaim'])->name('warranties.file_claim');
    Route::patch('/warranty_claims/{claim}', [WarrantyController::class, 'updateClaimStatus'])->name('warranty_claims.update_status');

    // Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // Users Management
    Route::resource('users', UserController::class);

    // Audit Trail Logs
    Route::get('/audit_logs', [AuditLogController::class, 'index'])->name('audit_logs.index');

    // Backup & Restore
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'createBackup'])->name('backups.create');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
});
