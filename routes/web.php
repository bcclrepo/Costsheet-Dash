<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\PublicCostsheetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\MineController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login',   [LoginController::class, 'showLogin'])->name('login');
Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/captcha',   [CaptchaController::class, 'generate'])->name('captcha');
// Public costsheet — no authentication required
Route::get('/costsheet', [PublicCostsheetController::class, 'index'])->name('public.costsheet');

Route::middleware(['auth'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Change password — any authenticated user
    Route::get('/password/change', [PasswordController::class, 'showForm'])->name('password.change');
    Route::put('/password/update',  [PasswordController::class, 'update'])->name('password.update');

    // Data view — all roles, but controller filters areas by access
    Route::get('/data', [DataController::class, 'view'])->name('data.view');

    // All Areas — super_admin and admin only
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::get('/data/all-areas', [DataController::class, 'allAreas'])->name('data.all-areas');
    });

    // Area-wise CSV upload — super_admin, admin, area_admin
    Route::middleware(['role:super_admin|admin|area_admin'])->group(function () {
        Route::get('/upload',              [UploadController::class, 'index'])->name('upload.index');
        Route::post('/upload/preview',     [UploadController::class, 'preview'])->name('upload.preview');
        Route::post('/upload/store',       [UploadController::class, 'store'])->name('upload.store');
        Route::get('/upload/template',     [UploadController::class, 'downloadTemplate'])->name('upload.template');
    });

    // Bulk (all-areas) upload — super_admin and admin only
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::get('/upload/bulk',          [UploadController::class, 'bulkIndex'])->name('upload.bulk.index');
        Route::post('/upload/bulk/preview', [UploadController::class, 'bulkPreview'])->name('upload.bulk.preview');
        Route::post('/upload/bulk/store',   [UploadController::class, 'bulkStore'])->name('upload.bulk.store');
        Route::get('/upload/bulk/template', [UploadController::class, 'bulkTemplate'])->name('upload.bulk.template');
    });

    // Super Admin only — user management
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    // Super Admin + Admin — master data management
    Route::middleware(['role:super_admin|admin'])->group(function () {
        Route::resource('areas', AreaController::class)->except(['show']);
        Route::resource('mines', MineController::class)->except(['show']);
    });

    // Super Admin only — activity logs
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/activity-logs',          [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/download', [ActivityLogController::class, 'downloadFile'])->name('activity-logs.download');
    });
});
