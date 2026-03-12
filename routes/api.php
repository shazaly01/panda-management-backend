<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- استيراد الـ Controllers الأساسية ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController; // تأكد من وجوده أو قم بتعديله
use App\Http\Controllers\Api\BackupController;

// --- استيراد Controllers النظام الجديد (الكتالوج والإدارة) ---
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\TreasuryController;
use App\Http\Controllers\Api\ItemMovementController;

// --- استيراد Controllers العمليات ---
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\PurchasesController;
use App\Http\Controllers\Api\TransfersController;
use App\Http\Controllers\Api\AdjustmentsController;

// --- Reports ---
use App\Http\Controllers\Api\Reports\InventoryReportController;
use App\Http\Controllers\Api\TreasuryTransactionController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة (Public Routes) ---
Route::post('/login', [AuthController::class, 'login']);


// --- المسارات المحمية (Protected Routes) ---
Route::middleware('auth:sanctum')->group(function () {

    // تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout']);

    // جلب بيانات المستخدم الحالي
    Route::get('/user', function (Request $request) {
        // تحميل الصلاحيات مع المستخدم للفرونت إند
        return $request->user()->load('roles.permissions');
    });


    // =================================================================
    // 1. إدارة المستخدمين والصلاحيات (System Administration)
    // =================================================================

    // جلب كل الصلاحيات (لواجهة إنشاء الأدوار)
    Route::get('roles/permissions', [RoleController::class, 'getAllPermissions'])->name('roles.permissions');

    Route::apiResource('roles', RoleController::class);
    Route::prefix('users')->group(function () {
        Route::get('/designers', [UserController::class, 'getDesigners']); // مسار المصممين (نصي) أولاً
        Route::get('/', [UserController::class, 'index']);                // عرض الكل
        Route::post('/', [UserController::class, 'store']);               // إنشاء
        Route::get('/{user}', [UserController::class, 'show']);           // عرض فردي (متغير) يوضع بعد النصي
        Route::put('/{user}', [UserController::class, 'update']);         // تحديث
        Route::delete('/{user}', [UserController::class, 'destroy']);      // حذف
    });

    // إدارة النسخ الاحتياطي (Backups)
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->middleware('can:backup.view');
        Route::post('/', [BackupController::class, 'store'])->middleware('can:backup.create');
        Route::get('/download', [BackupController::class, 'download'])->middleware('can:backup.download');
        Route::delete('/', [BackupController::class, 'destroy'])->middleware('can:backup.delete');
    });


    // =================================================================
    // 2. البيانات الأساسية (Catalog & Master Data)
    // =================================================================

    // الوحدات (Units)
    Route::apiResource('units', UnitController::class);

    // التصنيفات (Categories)
    // مسار مخصص للقائمة المسطحة (Dropdowns) قبل الـ resource لتجنب تداخل الـ ID
    Route::get('categories/list', [CategoryController::class, 'list']);
    Route::apiResource('categories', CategoryController::class);

    // الأصناف (Items)
    Route::apiResource('items', ItemController::class);


    // =================================================================
    // 3. الكيانات الإدارية والمالية (Entities)
    // =================================================================

    // المخازن (Warehouses)
    Route::post('warehouses/assign-user', [WarehouseController::class, 'assignToUser']); // إسناد صلاحيات المخازن
    Route::apiResource('warehouses', WarehouseController::class);

    // الشركاء (Partners - Customers & Suppliers)
    Route::apiResource('partners', PartnerController::class);

    // الخزائن والبنوك (Treasuries)
    Route::post('treasuries/assign-user', [TreasuryController::class, 'assignToUser']); // إسناد صلاحيات الخزائن للكاشير
    Route::apiResource('treasuries', TreasuryController::class);


    // =================================================================
    // 4. العمليات والحركات (Transactions)
    // =================================================================

    // حركات المخزون (Inventory: Adjustments & Transfers)
    Route::apiResource('inventory-transactions', InventoryController::class)->parameters([
    'inventory-transactions' => 'inventory'
]);

    // المبيعات (Sales)
    Route::apiResource('sales', SalesController::class);

    Route::patch('sales/{sale}/change-status', [\App\Http\Controllers\Api\SalesController::class, 'changeStatus']);

    // المشتريات (Purchases)
    Route::apiResource('purchases', PurchasesController::class);

    Route::post('transfers', [TransfersController::class, 'store']);

    Route::post('adjustments', [AdjustmentsController::class, 'store']);

    Route::apiResource('treasury-transactions', TreasuryTransactionController::class);

    Route::get('/item-movements', [ItemMovementController::class, 'index']);


    // =================================================================
    // 5. التقارير ولوحة التحكم (Dashboard)
    // =================================================================
    // (يمكنك تفعيل هذا المسار لاحقاً عند تحديث DashboardController ليناسب الجداول الجديدة)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.stats');

    Route::get('reports/stock-card', [InventoryReportController::class, 'stockCard']);

    Route::get('/reports/inventory', [ReportController::class, 'inventoryReport']);

    Route::get('/reports/item-movement', [ReportController::class, 'itemMovementReport']); // <--- أضف هذا الرابط


    Route::get('/reports/partner-balances', [ReportController::class, 'partnerBalancesReport']);


    Route::get('/reports/account-statement', [ReportController::class, 'accountStatementReport']);


   Route::get('/reports/designers', [ReportController::class, 'designersReport']);


});
