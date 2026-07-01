<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SalesHistoryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (Guest)
|--------------------------------------------------------------------------
*/
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |----------------------------------------------------------------------
    | Owner + Karyawan Routes
    |----------------------------------------------------------------------
    */

    // Dashboard (Owner sees full data, Karyawan sees limited)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kasir (Transaksi Baru)
    Route::get('/kasir', [TransactionController::class, 'create'])->name('kasir');
    Route::post('/kasir/store', [TransactionController::class, 'store'])->name('kasir.store');
    Route::get('/kasir/search', [TransactionController::class, 'searchProducts'])->name('kasir.search');

    // Inventaris (Cek Stok)
    Route::get('/inventaris', [InventoryController::class, 'index'])->name('inventaris');
    Route::get('/inventaris/{product}/movements', [InventoryController::class, 'stockMovements'])->name('inventaris.movements');

    // Penjualan (Riwayat Transaksi — Owner: semua, Karyawan: sendiri)
    Route::get('/penjualan', [SalesHistoryController::class, 'index'])->name('penjualan');
    Route::get('/penjualan/{transaction}', [SalesHistoryController::class, 'show'])->name('penjualan.show');

    // Restock (Owner + Karyawan with soft-block)
    Route::get('/restock', [RestockController::class, 'index'])->name('restock.index');
    Route::post('/restock/validate', [RestockController::class, 'validate_restock'])->name('restock.validate');
    Route::post('/restock', [RestockController::class, 'store'])->name('restock.store');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');

    /*
    |----------------------------------------------------------------------
    | Owner-Only Routes
    |----------------------------------------------------------------------
    */
    Route::middleware('role:owner')->group(function () {

        // Laporan Keuangan
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');

        // Pengaturan
        Route::get('/pengaturan', [SettingsController::class, 'index'])->name('pengaturan');
        Route::post('/pengaturan/telegram', [SettingsController::class, 'linkTelegram'])->name('pengaturan.telegram');

        // Produk Management
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Kategori Management
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Backup & Restore
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/create', [BackupController::class, 'create'])->name('backups.create');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
    });
});
