<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierCreditController;
use App\Http\Controllers\CustomerCreditController;
use App\Http\Controllers\StockOpnameController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    // ============================================
    // DASHBOARD ROUTES
    // ============================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart'])->name('dashboard.sales-chart');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // ============================================
    // PROFILE ROUTES
    // ============================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ============================================
    // CATEGORIES ROUTES
    // ============================================
    Route::get('/categories-export', [CategoryController::class, 'export'])->name('categories.export');
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
    Route::get('/categories/{category}/products', [CategoryController::class, 'getProductsByCategory'])->name('categories.products');

    // ============================================
    // PRODUCTS/STOCK ROUTES
    // ============================================
    Route::get('/products-export', [ProductController::class, 'export'])->name('products.export');
    Route::resource('products', ProductController::class);
    Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk-delete');

    // ============================================
    // STOCK MANAGEMENT ROUTES
    // ============================================
    Route::prefix('stock')->name('stock.')->group(function () {
        // Export & Generate Code (harus di atas routes dinamis)
        Route::get('/generate-code', [StockController::class, 'generateProductCode'])->name('generate-code');
        Route::get('/export', [StockController::class, 'export'])->name('export');
        
        // Main CRUD Routes
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::post('/', [StockController::class, 'store'])->name('store');
        Route::get('/{product}', [StockController::class, 'show'])->name('show');
        Route::get('/{product}/data', [StockController::class, 'getProductData'])->name('get-data');
        Route::get('/{product}/edit', [StockController::class, 'edit'])->name('edit');
        Route::put('/{product}', [StockController::class, 'update'])->name('update');
        Route::delete('/{product}', [StockController::class, 'destroy'])->name('destroy');
        
        // Stock Actions
        Route::post('/{product}/adjust-stock', [StockController::class, 'adjustStock'])->name('adjust');
    });

    // ============================================
    // STOCK OPNAMES ROUTES
    // ============================================
    Route::prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        // Export & Reports (harus di atas routes dinamis)
        Route::get('/export/data', [StockOpnameController::class, 'export'])->name('export');
        Route::get('/report/generate', [StockOpnameController::class, 'getReport'])->name('report');
        Route::get('/summary/stats', [StockOpnameController::class, 'getSummaryStats'])->name('summary-stats');
        
        // API endpoints (harus di atas routes dinamis)
        Route::get('/api/pending-count', [StockOpnameController::class, 'getPendingCount'])->name('api.pending-count');
        Route::get('/api/recent', [StockOpnameController::class, 'getRecentOpnames'])->name('api.recent');
        
        // Product Stock Info (harus di atas {stockOpname})
        Route::get('/product/{product}/stock-info', [StockOpnameController::class, 'getProductStock'])->name('product-stock');
        
        // Main CRUD Routes
        Route::get('/', [StockOpnameController::class, 'index'])->name('index');
        Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
        Route::post('/', [StockOpnameController::class, 'store'])->name('store');
        Route::get('/{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
        Route::get('/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('edit');
        Route::put('/{stockOpname}', [StockOpnameController::class, 'update'])->name('update');
        Route::delete('/{stockOpname}', [StockOpnameController::class, 'destroy'])->name('destroy');
        
        // Approval & Actions (harus setelah CRUD)
        Route::post('/{stockOpname}/approve', [StockOpnameController::class, 'approve'])->name('approve');
        Route::post('/{stockOpname}/reject', [StockOpnameController::class, 'reject'])->name('reject');
        Route::get('/{stockOpname}/print', [StockOpnameController::class, 'print'])->name('print');
        
        // Bulk Operations
        Route::post('/bulk-approve', [StockOpnameController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-delete', [StockOpnameController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // ============================================
    // CUSTOMER ROUTES (Modal Based)
    // ============================================
    Route::prefix('customers')->name('customers.')->group(function () {
        // Export & Reports (harus di atas routes dinamis)
        Route::get('/export/data', [CustomerController::class, 'export'])->name('export');
        Route::get('/top/list', [CustomerController::class, 'getTopCustomers'])->name('top');
        
        // Search & Autocomplete (harus di atas routes dinamis)
        Route::get('/search/autocomplete', [CustomerController::class, 'search'])->name('search');
        
        // Bulk Operations (harus di atas routes dinamis)
        Route::post('/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Main CRUD Routes
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        
        // Additional Features (harus setelah routes dinamis utama)
        Route::post('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{customer}/info', [CustomerController::class, 'getCustomerInfo'])->name('info');
    });

    // ============================================
    // SUPPLIER ROUTES (Modal Based)
    // ============================================
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        // Export & Reports (harus di atas routes dinamis)
        Route::get('/export/data', [SupplierController::class, 'export'])->name('export');
        Route::get('/report/generate', [SupplierController::class, 'getSupplierReport'])->name('report');
        
        // Search & Autocomplete (harus di atas routes dinamis)
        Route::get('/search/ajax', [SupplierController::class, 'search'])->name('search');
        Route::get('/active/list', [SupplierController::class, 'getActiveSuppliers'])->name('active');
        
        // Bulk Operations (harus di atas routes dinamis)
        Route::post('/bulk-delete', [SupplierController::class, 'bulkDelete'])->name('bulk-delete');
        
        // Main CRUD Routes
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
        
        // Additional Features (harus setelah routes dinamis utama)
        Route::post('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{supplier}/info', [SupplierController::class, 'getSupplierInfo'])->name('info');
    });

    // ============================================
    // SALES ROUTES
    // ============================================
    Route::prefix('sales')->name('sales.')->group(function () {
        // Export (harus di atas routes dinamis)
        Route::get('/export', [SalesController::class, 'export'])->name('export');
        
        // Search & Info (harus di atas routes dinamis)
        Route::get('/search/products', [SalesController::class, 'searchProducts'])->name('search-products');
        Route::get('/product/{product}/info', [SalesController::class, 'getProductInfo'])->name('product-info');
        
        // Reports (harus di atas routes dinamis)
        Route::get('/reports/sales', [SalesController::class, 'getSalesReport'])->name('report');
        Route::get('/reports/due-credits', [SalesController::class, 'getDueCustomerCredits'])->name('due-credits');
        Route::get('/reports/credit-stats', [SalesController::class, 'getCustomerCreditStats'])->name('credit-stats');
        
        // Sales by Customer (untuk dropdown di Customer Credit - harus di atas {sale})
        Route::get('/by-customer/{customer}', [SalesController::class, 'byCustomer'])->name('by-customer');
        
        // Main CRUD Routes
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('/create', [SalesController::class, 'create'])->name('create');
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('/{sale}', [SalesController::class, 'show'])->name('show');
        Route::get('/{sale}/edit', [SalesController::class, 'edit'])->name('edit');
        Route::put('/{sale}', [SalesController::class, 'update'])->name('update');
        Route::delete('/{sale}', [SalesController::class, 'destroy'])->name('destroy');
        
        // Transaction Details (harus setelah CRUD)
        Route::get('/{sale}/details', [SalesController::class, 'getTransactionDetails'])->name('details');
        Route::get('/{sale}/print-receipt', [SalesController::class, 'printReceipt'])->name('print-receipt');
        Route::get('/{sale}/download-receipt', [SalesController::class, 'downloadReceipt'])->name('download-receipt');
        Route::post('/{sale}/confirm-payment', [SalesController::class, 'confirmPayment'])->name('confirm-payment');
    });

    // ============================================
    // PURCHASES ROUTES
    // ============================================
    Route::prefix('purchases')->name('purchases.')->group(function () {
        // Export & Reports (harus di atas routes dinamis)
        Route::get('/export', [PurchaseController::class, 'export'])->name('export');
        Route::get('/api/report', [PurchaseController::class, 'getPurchaseReport'])->name('api.report');
        Route::get('/api/credit-purchases', [PurchaseController::class, 'getCreditPurchases'])->name('api.credit-purchases');
        
        // API endpoints for search (harus di atas {purchase})
        Route::get('/api/search-products', [PurchaseController::class, 'searchProducts'])->name('api.search-products');
        Route::get('/api/product/{product}', [PurchaseController::class, 'getProductInfo'])->name('api.product-info');
        Route::get('/api/due-credits', [PurchaseController::class, 'getDueSupplierCredits'])->name('api.due-credits');
        Route::get('/api/credit-stats', [PurchaseController::class, 'getSupplierCreditStats'])->name('api.credit-stats');
        
        // Main CRUD Routes
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
        Route::get('/{purchase}/edit', [PurchaseController::class, 'edit'])->name('edit');
        Route::put('/{purchase}', [PurchaseController::class, 'update'])->name('update');
        Route::delete('/{purchase}', [PurchaseController::class, 'destroy'])->name('destroy');
        
        // Payment & Confirmation (harus setelah CRUD)
        Route::post('/{purchase}/process-payment', [PurchaseController::class, 'processPayment'])->name('process-payment');
        Route::post('/{purchase}/confirm-receipt', [PurchaseController::class, 'confirmReceipt'])->name('confirm-receipt');
        
        // Print
        Route::get('/{purchase}/print-order', [PurchaseController::class, 'printPurchaseOrder'])->name('print-order');
        Route::get('/{purchase}/print-receipt', [PurchaseController::class, 'printReceipt'])->name('print-receipt');
    });

    // ============================================
    // SUPPLIER CREDITS ROUTES
    // ============================================
    Route::prefix('supplier-credits')->name('supplier-credits.')->group(function () {
        // Reports & API (harus di atas routes dinamis)
        Route::get('/report/generate', [SupplierCreditController::class, 'getReport'])->name('report');
        Route::get('/due/list', [SupplierCreditController::class, 'getDueCredits'])->name('due');
        
        // Main CRUD Routes
        Route::get('/', [SupplierCreditController::class, 'index'])->name('index');
        Route::get('/{supplierCredit}', [SupplierCreditController::class, 'show'])->name('show');
        Route::get('/{supplierCredit}/edit', [SupplierCreditController::class, 'edit'])->name('edit');
        Route::put('/{supplierCredit}', [SupplierCreditController::class, 'update'])->name('update');
        Route::delete('/{supplierCredit}', [SupplierCreditController::class, 'destroy'])->name('destroy');
        
        // Payment & Actions (harus setelah CRUD)
        Route::post('/{supplierCredit}/process-payment', [SupplierCreditController::class, 'processPayment'])->name('process-payment');
        Route::post('/{supplierCredit}/send-reminder', [SupplierCreditController::class, 'sendReminder'])->name('send-reminder');
        Route::post('/{supplierCredit}/confirm-receipt', [SupplierCreditController::class, 'confirmReceipt'])->name('confirm-receipt');
    });

    // ============================================
    // CUSTOMER CREDITS ROUTES
    // ============================================
    Route::prefix('customer-credits')->name('customer-credits.')->group(function () {
        // Export & Reports (harus di atas routes dinamis)
        Route::get('/export/data', [CustomerCreditController::class, 'export'])->name('export');
        Route::get('/report/generate', [CustomerCreditController::class, 'getReport'])->name('report');
        Route::get('/due/list', [CustomerCreditController::class, 'getDueCredits'])->name('due');
        Route::get('/stats/summary', [CustomerCreditController::class, 'getStats'])->name('stats');
        
        // Search & Info (harus di atas routes dinamis)
        Route::get('/search/ajax', [CustomerCreditController::class, 'search'])->name('search');
        
        // Customer Credit Info by Customer (harus di atas routes dinamis utama)
        Route::get('/customer/{customer}/info', [CustomerCreditController::class, 'getCustomerCreditInfo'])->name('customer-info');
        
        // Main CRUD Routes
        Route::get('/', [CustomerCreditController::class, 'index'])->name('index');
        Route::get('/create', [CustomerCreditController::class, 'create'])->name('create');
        Route::post('/', [CustomerCreditController::class, 'store'])->name('store');
        Route::get('/{customerCredit}', [CustomerCreditController::class, 'show'])->name('show');
        Route::get('/{customerCredit}/edit', [CustomerCreditController::class, 'edit'])->name('edit');
        Route::put('/{customerCredit}', [CustomerCreditController::class, 'update'])->name('update');
        Route::delete('/{customerCredit}', [CustomerCreditController::class, 'destroy'])->name('destroy');
        
        // Payment & Actions (harus setelah CRUD)
        Route::post('/{customerCredit}/accept-payment', [CustomerCreditController::class, 'acceptPayment'])->name('accept-payment');
        Route::post('/{customerCredit}/send-reminder', [CustomerCreditController::class, 'sendReminder'])->name('send-reminder');
    });

    // ============================================
    // FINANCIAL REPORTS ROUTES
    // ============================================
    Route::prefix('financial-reports')->name('financial-reports.')->group(function () {
        // Main Reports
        Route::get('/', [FinancialReportController::class, 'index'])->name('index');
        Route::get('/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('profit-loss');
        
        // Data APIs
        Route::get('/chart-data', [FinancialReportController::class, 'getChartData'])->name('chart-data');
        Route::get('/profit-loss-data', [FinancialReportController::class, 'getProfitLossData'])->name('profit-loss-data');
        
        // Export
        Route::post('/export', [FinancialReportController::class, 'export'])->name('export');
    });

    // ============================================
    // STOCK REPORTS ROUTES
    // ============================================
    Route::prefix('stock-reports')->name('stock-reports.')->group(function () {
        Route::get('/', [StockReportController::class, 'index'])->name('index');
        Route::get('/chart-data', [StockReportController::class, 'getChartData'])->name('chart-data');
        Route::post('/export', [StockReportController::class, 'export'])->name('export');
    });

    // ============================================
    // SETTINGS ROUTES
    // ============================================
    Route::prefix('settings')->name('settings.')->group(function () {
        // Main Settings Page
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        
        // Store Profile Settings
        Route::post('/update-store-profile', [SettingsController::class, 'updateStoreProfile'])->name('update-store-profile');
        
        // User Settings
        Route::post('/update-user-settings', [SettingsController::class, 'updateUserSettings'])->name('update-user-settings');
        
        // System Settings
        Route::post('/update-system-settings', [SettingsController::class, 'updateSystemSettings'])->name('update-system-settings');
        
        // Notification Settings
        Route::post('/update-notification-settings', [SettingsController::class, 'updateNotificationSettings'])->name('update-notification-settings');
        
        // Security Settings
        Route::post('/update-security-settings', [SettingsController::class, 'updateSecuritySettings'])->name('update-security-settings');
        
        // Backup & Restore
        Route::post('/create-backup', [SettingsController::class, 'createBackup'])->name('create-backup');
        Route::post('/restore-backup', [SettingsController::class, 'restoreBackup'])->name('restore-backup');
        Route::delete('/delete-backup', [SettingsController::class, 'deleteBackup'])->name('delete-backup');
        Route::get('/backup-files', [SettingsController::class, 'getBackupFiles'])->name('backup-files');
        Route::get('/download-backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('download-backup');
        Route::post('/upload-backup', [SettingsController::class, 'uploadBackup'])->name('upload-backup');
        
        // Import & Export Settings
        Route::get('/export-settings', [SettingsController::class, 'exportSettings'])->name('export-settings');
        Route::post('/import-settings', [SettingsController::class, 'importSettings'])->name('import-settings');
        
        // Reset Settings
        Route::post('/reset-settings', [SettingsController::class, 'resetSettings'])->name('reset-settings');
        
        // User Management (untuk bagian Manajemen Pengguna)
        Route::get('/users', [SettingsController::class, 'getUsers'])->name('users');
        Route::post('/users', [SettingsController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [SettingsController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [SettingsController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{user}/toggle-status', [SettingsController::class, 'toggleUserStatus'])->name('users.toggle-status');
    });
});

// ============================================================
// API ROUTES (untuk AJAX calls)
// ============================================================
Route::prefix('api')->middleware(['auth'])->name('api.')->group(function () {
    // Products API
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/{product}/info', [ProductController::class, 'getProductInfo'])->name('info');
    });
    
    // Customers API
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::get('/{customer}/info', [CustomerController::class, 'getCustomerInfo'])->name('info');
    });
    
    // Suppliers API
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/search', [SupplierController::class, 'search'])->name('search');
        Route::get('/{supplier}/info', [SupplierController::class, 'getSupplierInfo'])->name('info');
        Route::get('/active', [SupplierController::class, 'getActiveSuppliers'])->name('active');
    });
    
    // Sales API
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesController::class, 'apiIndex'])->name('index');
        Route::get('/stats', [SalesController::class, 'getStats'])->name('stats');
    });
    
    // Purchases API
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/stats', [PurchaseController::class, 'getStats'])->name('stats');
    });
    
    // Customer Credits API
    Route::prefix('customer-credits')->name('customer-credits.')->group(function () {
        Route::get('/stats', [CustomerCreditController::class, 'getStats'])->name('stats');
        Route::get('/due', [CustomerCreditController::class, 'getDueCredits'])->name('due');
    });
    
    // Stock Opnames API
    Route::prefix('stock-opnames')->name('stock-opnames.')->group(function () {
        Route::get('/pending', [StockOpnameController::class, 'getPendingCount'])->name('pending');
        Route::get('/recent', [StockOpnameController::class, 'getRecentOpnames'])->name('recent');
        Route::get('/stats', [StockOpnameController::class, 'getStats'])->name('stats');
    });
    
    // Dashboard API
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    });
});

require __DIR__.'/auth.php';